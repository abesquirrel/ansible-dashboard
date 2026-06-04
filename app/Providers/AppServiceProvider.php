<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AnsibleSSHService;
use App\Services\AnsibleService;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AnsibleSSHService::class, fn () => new AnsibleSSHService());
        $this->app->singleton(AnsibleService::class, fn ($app) => new AnsibleService($app->make(AnsibleSSHService::class)));
    }

    public function boot(): void
    {
        Gate::define('admin', function (User $user) {
            return $user->is_admin === true;
        });
    }
}
