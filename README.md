# Square1\ResponseCache

This package allows to store an entire response in cache, adding some useful headers commonly used by third party services like varnish, CloudFlare and so.

## Install

Using Composer

```bash
composer require square1/response-cache
```

Once installed, please add the cache-middleware to your `kernel.php` file:

```php
protected $routeMiddleware = [
    //...
    'cache' = \Square1\ResponseCache\Middleware\ResponseCacheMiddleware::class,
    //...
];
```

## Usage

Add the middleware to the route or group of routes you want to cache:

```php
Route::get('/my-route', 'MyController')->middleware('cache');
```

## Cache Profiles

The cache profiles will give you full control on how to add or remove any particular route from the cache

```php
<?php

namespace App\CacheProfiles;

use Square1\ResponseCache\Profiles\BaseCacheProfile

class ArticleCacheProfile extends BaseCacheProfile {
    /** time in seconds **/
    protected $ttl = 3600;

    /** Tags to 'flush' when removed **/
    protected $flushCacheTags = [];

    /** Current profile identifier tags **/
    protected $cacheTags = [];

    /** Cache any response with the following statuses **/
    protected $cacheStatus = [200];

    /**
     * Decide wether the current request should be cached or not
     * @param Illuminate\Http\Request $request
     * return boolean
     **/
    public function shouldBeCached(Request $request)
    {
        return $request->isMethod('GET');
    }
}
```

## Remove a response from the cache

By creating a new instance of the cache-profile you can get and remove any request from the cache:

```php
    $profile = new ArticleCacheProfile($request);

    $responseCache = resolve(ResponseCache::class);

    $responseCache->withTags($profile->getTags())->flush();
```

You can use the `$flushCacheTags` attribute in your cache-profile to reference other profile tags, like so:

```php
class ArticleCacheProfile {
    protected $cacheTags = ['article'];
    protected $flushCacheTags = ['homepage', 'category-page'];
}

class HomeCacheProfile {
    protected $cacheTags = ['homepage'];
}

class CategoriesCacheProfile {
    protected $cacheTags = ['category-page'];
}
```

Using that crossed reference, now you could for instance, remove the "homepage" and "category-page" from the cache while removing any "article" from the cache, like so:

```php
    $profile = new ArticleCacheProfile($request);

    $cacheResponse = resolve(CacheResponse::class);

    /** Clear the cache for a given URL */
    $cacheResponse->withTags($profile->getTags())->forget(url('/some-article-url'));

    // Flush the cache for ALL the referenced routes ('homepage' and 'category-page')
    Cache::tags($profile->getFlushCacheTags())->flush();
```

## Roadmap

- Package auto-discovery
- open a new 'PURGE' route