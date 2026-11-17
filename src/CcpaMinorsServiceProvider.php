<?php

namespace JeffersonGoncalves\CcpaMinors;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use JeffersonGoncalves\CcpaMinors\Commands\MinorsCommand;
use JeffersonGoncalves\CcpaMinors\Http\Middleware\EnsureVerifiedAdult;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CcpaMinorsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ccpa-minors')
            ->hasConfigFile()
            ->hasMigrations(['create_ccpa_minors_tables'])
            ->hasCommand(MinorsCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(MinorsManager::class);
    }

    public function packageBooted(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('ccpa.adult', EnsureVerifiedAdult::class);

        Blade::if('ccpaMinorSaleAllowed', function (): bool {
            $user = auth()->user();

            return $this->app->make(MinorsManager::class)->allowsSaleOrSharing($user instanceof Model ? $user : null);
        });
    }
}
