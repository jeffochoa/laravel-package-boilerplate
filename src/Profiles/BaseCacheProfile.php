<?php

namespace Square1\ResponseCache\Profiles;

use Illuminate\Http\Request;

abstract class BaseCacheProfile
{
    protected $ttl;

    protected $flushCacheTags = [];

    protected $cacheTags = [];

    protected $cacheStatus = [200];

    public function setTtl(int $seconds)
    {
        $this->ttl = $seconds;
    }

    public function setCacheTags(array $tags)
    {
        $this->cacheTags = $tags;
    }

    public function getFlushCacheTags()
    {
        return $this->flushCacheTags;
    }

    public function getTtl()
    {
        return $this->ttl;
    }

    public function getTags()
    {
        return array_merge($this->cacheTags, $this->flushCacheTags);
    }

    public function hasTags()
    {
        return ! empty($this->cacheTags);
    }

    public function shouldBeCached(Request $request)
    {
        return $request->isMethod('GET');
    }

    public function shouldNotBeCached(Request $request)
    {
        return ! $this->shouldBeCached($request);
    }

    public function shouldCacheStatus(int $status) : bool
    {
        return in_array($status, $this->cacheStatus);
    }
}
