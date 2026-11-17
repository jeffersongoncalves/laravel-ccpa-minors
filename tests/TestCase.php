<?php

namespace JeffersonGoncalves\CcpaMinors\Tests;

use JeffersonGoncalves\CcpaMinors\CcpaMinorsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CcpaMinorsServiceProvider::class,
        ];
    }
}
