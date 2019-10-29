<?php

namespace Square1\ResponseCache\Middleware;

use Closure;
use Exception;
use Square1\ResponseCache\ResponseCache;

class ResponseCacheMiddleware
{
    public function handle($request, Closure $next, string $profile = 'default')
    {

        if (($profile = config("response-cache.profiles.{$profile}", false)) == false) {
            throw new Exception("The profile $profile was not found in the config('response-cache.profiles') array.");
        }

        $profile = new $profile($request);

        if ($profile->shouldNotBeCached($request)) {
            return $next($request);
        }

        $responseCache = resolve(ResponseCache::class);

        if ($profile->hasTags()) {
            $responseCache = $responseCache->withTags($profile->getTags());
        }

        if ($responseCache->isCached($request)) {
            return $responseCache->buildResponseFromCached($request);
        }

        $response = $next($request);

        if ($profile->shouldCacheStatus($response->getStatusCode())) {
            $responseCache->store($response, $request, $profile->getTtl());
        }

        return $response;
    }
}
