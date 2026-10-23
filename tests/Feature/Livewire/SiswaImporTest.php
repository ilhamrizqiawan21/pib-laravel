<?php

use App\Livewire\SiswaImpor;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('tamu diarahkan ke login saat mengakses halaman impor siswa', function () {
    $this->get('/siswa/impor')->assertRedirect('/login');
});

test('pengguna dapat mengakses halaman impor siswa dan mengunggah berkas', function () {
    $user = User::factory()->create();
    $class = SchoolClass::factory()->create(['name' => 'VII-A']);

    $csvContent = implode("\n", [
        'nama,kelas,nis,nisn,jenis_kelamin',
        'Bilal bin Rabah,VII-A,7777,,L',
    ]);

    $file = UploadedFile::fake()->createWithContent('siswa.csv', $csvContent);

    Livewire::actingAs($user)
        ->test(SiswaImpor::class)
        ->set('file', $file)
        ->call('import')
        ->assertHasNoErrors()
        ->assertSee('Berhasil mengimpor 1 siswa');

    expect(Student::query()->where('name', 'Bilal bin Rabah')->exists())->toBeTrue();
});
