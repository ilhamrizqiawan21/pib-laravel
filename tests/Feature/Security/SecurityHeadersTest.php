<?php

use Illuminate\Support\Facades\Blade;

test('respons web menyertakan security headers wajib', function () {
    $response = $this->get('/login');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    $response->assertHeader('Content-Security-Policy');

    $csp = (string) $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'");
    expect($csp)->toContain("form-action 'self'");
});

test('hsts ditambahkan pada lingkungan produksi', function () {
    app()->detectEnvironment(fn () => 'production');

    $response = $this->get('/login');

    $response->assertHeader('Strict-Transport-Security');
    expect((string) $response->headers->get('Strict-Transport-Security'))
        ->toContain('max-age=31536000');
});

test('pesan toast sesi di-encode aman untuk konteks javascript', function () {
    $payload = "');window.pibInjected=true;//</script>";
    session()->flash('error', $payload);

    $html = Blade::render('<x-toast />');

    expect($html)->not->toContain($payload);
    expect($html)->toContain('\\u003C');
});
