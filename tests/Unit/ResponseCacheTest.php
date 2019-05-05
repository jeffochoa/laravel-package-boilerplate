<?php

namespace Square1\ResponseCache\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use Square1\ResponseCache\Hasher;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;
use Square1\ResponseCache\ResponseCache;
use Square1\ResponseCache\Tests\TestCase;
use Square1\ResponseCache\Profiles\DefaultCacheProfile;
use Square1\ResponseCache\Middleware\ResponseCacheMiddleware;
use Illuminate\Support\Facades\Route;

class ResponseCacheTest extends TestCase
{
    /** @test */
    public function save_response_in_cache()
    {
        $response = $this->get('/');

        $responseCache = resolve(ResponseCache::class);

        $request = Request::create(URL::to('/'));

        $responseCache->store($response, $request, $seconds = 10);

        $this->assertEquals($responseCache->serialize($response, $seconds), $responseCache->getCached($request));
    }

    /** @test */
    public function check_if_a_request_is_already_on_cache()
    {
        $response = $this->get('/some-route');
        $request = Request::create(URL::to('/some-route'));

        $responseCache = resolve(ResponseCache::class);
        $responseCache->store($response, $request, 10);

        $this->assertTrue($responseCache->isCached($request));
    }

    /** @test */
    public function cache_with_tags()
    {
        $initialResponse = $this->get('/some-route');
        $request = Request::create(URL::to('/some-route'));
        $hasher = new Hasher;
        $seconds = 10;

        $responseCache = resolve(ResponseCache::class);
        $responseCache->withTags(['tag1', 'tag2'])->store($initialResponse, $request, $seconds);

        $taggedResponse = Cache::tags(['tag1', 'tag2'])->get($hasher->make($request));

        $this->assertTrue($responseCache->isCached($request));
        $this->assertEquals($responseCache->serialize($initialResponse, $seconds), $responseCache->getCached($request));
    }

    /** @test */
    public function get_cached_response_headers()
    {
        $initialResponse = $this->get('/some-route');
        $now = Carbon::now();
        $request = Request::create(URL::to('/some-route'));

        $responseCache = resolve(ResponseCache::class);
        $responseCache->store($initialResponse, $request, 10);

        $resp = $responseCache->buildResponseFromCached($request);

        $this->assertTrue($resp->headers->has('cache-control'));
        $this->assertTrue($resp->headers->has('last-modified'));
        $this->assertTrue($resp->headers->has('expires'));
        $this->assertTrue($resp->headers->has('etag'));
        $this->assertTrue($resp->headers->hasCacheControlDirective('public'));
        $this->assertTrue($resp->headers->hasCacheControlDirective('s-maxage'));
        $this->assertTrue($resp->headers->hasCacheControlDirective('max-age'));
        $this->assertFalse($resp->headers->hasCacheControlDirective('no-cache'));
    }

    /** @test */
    public function cache_using_middleware()
    {
        Cache::flush();
        $this->app[Router::class]->aliasMiddleware('cache', ResponseCacheMiddleware::class);

        Route::any('app.test/a-route', function () {
            return response()->json(['success' => true], 200);
        })->middleware('cache:default');

        $firstRequest = $this->call('GET', '/a-route');
        $secondRequest = Request::create(URL::to('app.test/a-route'), 'GET');

        $profile = new DefaultCacheProfile($secondRequest);
        $responseCache = resolve(ResponseCache::class)->withTags($profile->getTags());

        $this->assertTrue($responseCache->isCached($secondRequest));
    }
}
