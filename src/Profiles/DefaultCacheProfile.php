<?php

namespace Square1\ResponseCache\Profiles;

class DefaultCacheProfile extends BaseCacheProfile
{

    public function __construct()
    {
        $this->setTtl(config('response-cache.ttl'));
        $this->setCacheTags(config('response-cache.tags'));
    }
}
