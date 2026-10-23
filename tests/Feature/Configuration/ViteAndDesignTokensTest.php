<?php

test('manifest vite memuat aset css dan js lokal', function () {
    $manifestPath = public_path('build/manifest.json');

    expect(file_exists($manifestPath))->toBeTrue();

    $manifest = json_decode((string) file_get_contents($manifestPath), true);

    expect($manifest)->toHaveKey('resources/css/app.css');
    expect($manifest)->toHaveKey('resources/js/app.js');
});

test('konfigurasi css memuat token desain dan font inter lokal', function () {
    $css = (string) file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('@import \'@fontsource-variable/inter\';');
    expect($css)->toContain('--brand: #0d9488;');
    expect($css)->toContain('--focus: #5eead4;');
    expect($css)->toContain('--radius: 14px;');
    expect($css)->toContain('--color-brand: var(--brand);');
});

test('vite config tidak bergantung pada cdn pihak ketiga', function () {
    $viteConfig = (string) file_get_contents(base_path('vite.config.js'));

    expect($viteConfig)->not->toContain('bunny');
    expect($viteConfig)->not->toContain('fonts.googleapis.com');
});
