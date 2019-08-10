<?php

namespace Square1\ResponseCache;

use Illuminate\Http\Request;
use Illuminate\Cache\Repository;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Carbon;
use Illuminate\Http\Response;

/**
 * Delete this folder and have fun
 * creating your package.
 */
class ResponseCache
{
    protected $cacheTags = [];

    protected $hasher;

    protected $cacheRepository;

    public function __construct(Hasher $hasher, Repository $cacheRepository)
    {
        $this->hasher = $hasher;
        $this->cacheRepository = $this->resolveCacheRepository($cacheRepository);
    }

    protected function resolveCacheRepository(Repository $cacheRepository)
    {
        if ($cacheRepository instanceof TaggableStore) {
            return $cacheRepository->tags(config('response-cache.tags'));
        }
        return $cacheRepository;
    }

    public function withTags(array $tags)
    {
        $this->cacheRepository = $this->cacheRepository->tags(array_merge(config('response-cache.tags'), $tags));
        return $this;
    }

    public function isCached(Request $request) : bool
    {
        return !empty($this->cacheRepository->get($this->hasher->make($request)));
    }

    public function store($response, $request, $seconds)
    {
        $response = $this->addHeaders($this->clone($response), $seconds);
        return $this->cacheRepository->put($this->hasher->make($request), $this->serialize($response, $seconds), $seconds);
    }

    /**
     * The addHeaders method mutates the request object.
     * By using "clone" we prevent the original response to
     * be changed in the first request (when not cached).
     */
    protected function clone($response)
    {
        return clone $response;
    }

    protected function addHeaders($response, $seconds)
    {
        $currentTime = Carbon::now();
        $expirationTime = Carbon::now()->addSeconds($seconds);
        $response->setLastModified($currentTime);
        $response->setETag(md5($expirationTime));
        $response->setExpires($expirationTime);
        $response->setTtl($seconds);
        $response->setPublic();
        $response->setMaxAge($seconds);

        return $response;
    }

    public function serialize($response, int $seconds = 0) : string
    {
        return serialize([
            'content'    => $response->getContent(),
            'headers'    => $response->headers,
            'version'    => $response->getProtocolVersion(),
            'statusCode' => $response->getStatusCode(),
            'charset'    => $response->getCharset(),
            'protocolVersion' => $response->getProtocolVersion(),
            'lastModified' => $response->getLastModified(),
            'Etag' => $response->getEtag(),
            'expires' => $response->getExpires(),
            'ttl' => $seconds
        ]);
    }

    public function buildResponseFromCached($request)
    {
        $payload = unserialize($this->getCached($request));
        $response = Response::create($payload['content'], $payload['statusCode']);

        $response->headers = $payload['headers'];

        if (!empty($payload['charset'])) {
            $response->setCharset($payload['charset']);
        }

        $response->setProtocolVersion($payload['protocolVersion']);
        $response->setLastModified($payload['lastModified']);
        $response->setETag($payload['Etag']);
        $response->setExpires($payload['expires']);
        $response->setTtl($payload['ttl']);
        $response->setPublic();
        $response->headers->removeCacheControlDirective('no-cache');

        return $response;
    }

    public function getCached($request)
    {
        return $this->cacheRepository->get($this->hasher->make($request));
    }

    public function forget($url)
    {
        $request = Request::create($url);
        if ($this->isCached($request)) {
            $this->cacheRepository->forget($this->hasher->make($request));
        }
    }
}
