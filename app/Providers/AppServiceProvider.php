<?php

namespace App\Providers;

use App\Enums\Role;
use App\Http\Middleware\EnsureActiveUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        Model::shouldBeStrict(! $this->app->isProduction());

        Gate::define('admin', fn (User $user) => $user->role === Role::Admin);

        Livewire::addPersistentMiddleware(EnsureActiveUser::class);
        Livewire::setUpdateRoute(fn ($handle, $path) => Route::post($path, $handle)
            ->middleware('throttle:120,1')
            ->name('default-livewire.update'));
    }
}
