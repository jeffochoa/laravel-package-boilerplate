<?php

namespace Square1\ResponseCache\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Square1\ResponseCache\Providers\ResponseCacheServiceProvider;

class TestCase extends BaseTestCase
{
    // Here you can add global testing functions
    protected function getPackageProviders($app)
    {
        return [
            ResponseCacheServiceProvider::class
        ];
    }
}
