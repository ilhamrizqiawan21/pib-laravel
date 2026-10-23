<?php

test('konfigurasi github actions ci memuat langkah-langkah wajib', function () {
    $ciPath = base_path('.github/workflows/ci.yml');

    expect(file_exists($ciPath))->toBeTrue();

    $content = (string) file_get_contents($ciPath);

    expect($content)->toContain('php-version: \'8.5\'')
        ->toContain('composer install')
        ->toContain('composer audit')
        ->toContain('npm ci')
        ->toContain('npm run build')
        ->toContain('composer check');
});
