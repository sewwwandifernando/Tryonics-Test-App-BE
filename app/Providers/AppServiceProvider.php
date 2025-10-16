<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         $permissions = [
            'view posts',
            'create posts',
            'edit posts',
            'delete posts',
            'view own posts',
            'edit own posts',
            'delete own posts',
        ];
            Passport::tokensCan(
                $permissions
            );
    }
}
