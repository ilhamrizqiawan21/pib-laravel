<?php

use App\Models\User;

test('favicon tersedia sebagai berkas ikon yang valid', function () {
    $path = public_path('favicon.ico');
    $data = file_get_contents($path);

    expect($data)->not->toBeFalse()
        ->and(strlen($data))->toBeGreaterThan(100)
        ->and(substr($data, 0, 4))->toBe("\x00\x00\x01\x00");
});

test('halaman login dan aplikasi merujuk favicon', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('favicon.ico')
        ->assertSee('favicon.svg');

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('favicon.ico')
        ->assertSee('favicon.svg');
});
