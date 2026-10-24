<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;

test('halaman utama mengharuskan autentikasi dan membawa pengguna ke beranda', function () {
    $this->get('/')->assertRedirect('/login');

    $this->actingAs(User::factory()->create(['is_active' => true]))
        ->get('/')
        ->assertRedirect('/dashboard');
});

test('rute aplikasi mewajibkan autentikasi dan middleware pengguna aktif', function () {
    foreach (Route::getRoutes() as $route) {
        $uri = $route->uri();
        $name = (string) $route->getName();

        if ($uri === 'login' || $uri === 'up' || str_starts_with($uri, 'livewire-') || str_starts_with($name, 'storage.')) {
            continue;
        }

        expect($route->gatherMiddleware())->toContain('auth')
            ->and($route->gatherMiddleware())->toContain(EnsureActiveUser::class);
    }
});

test('panggilan Livewire tetap menolak sesi pengguna yang sudah dinonaktifkan', function () {
    $middleware = app(PersistentMiddleware::class)->getPersistentMiddleware();

    expect($middleware)
        ->toContain(EnsureActiveUser::class)
        ->toContain(Authenticate::class)
        ->toContain(Authorize::class);
});

test('berkas disk lokal tidak dibuka melalui endpoint HTTP otomatis', function () {
    expect(Route::getRoutes()->getByName('storage.local'))->toBeNull()
        ->and(Route::getRoutes()->getByName('storage.local.upload'))->toBeNull();

    $this->get('/storage/private/backup.json')->assertNotFound();
    $this->put('/storage/private/upload.json', ['payload' => 'payload'])->assertNotFound();
});

test('unggahan sementara Livewire menolak permintaan tanpa tanda tangan', function () {
    $previewRoute = Route::getRoutes()->getByName('livewire.preview-file');
    expect($previewRoute)->not->toBeNull();
    $this->get('/'.str_replace('{filename}', 'unknown.txt', $previewRoute->uri()))
        ->assertUnauthorized();

    $uploadRoute = Route::getRoutes()->getByName('livewire.upload-file');
    expect($uploadRoute)->not->toBeNull();
    $this->post('/'.$uploadRoute->uri(), ['files' => []])->assertUnauthorized();
});

test('endpoint publik dan penulisan berat memiliki rate limit', function () {
    foreach (['backup.manual', 'admin.restore', 'default-livewire.update', 'livewire.upload-file'] as $routeName) {
        $route = Route::getRoutes()->getByName($routeName);

        expect($route)->not->toBeNull();
        expect(implode('|', $route->gatherMiddleware()))->toContain('throttle:');
    }

    $loginRoute = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($route) => $route->uri() === 'login' && in_array('POST', $route->methods(), true));

    expect($loginRoute)->not->toBeNull();
    expect(implode('|', $loginRoute->gatherMiddleware()))->toContain('throttle:30,1');
});

test('endpoint POST aplikasi berada pada middleware perlindungan CSRF', function () {
    expect(app(Router::class)->getMiddlewareGroups()['web'])
        ->toContain(PreventRequestForgery::class);
});
