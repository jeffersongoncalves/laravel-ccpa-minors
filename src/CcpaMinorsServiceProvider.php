<?php

namespace JeffersonGoncalves\CcpaMinors;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CcpaMinorsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-ccpa-minors')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
