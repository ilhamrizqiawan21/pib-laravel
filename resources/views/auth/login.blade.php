<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-canvas text-ink antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Masuk — {{ config('app.name', 'PIB Penilaian') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 font-sans bg-canvas">
    <div class="w-full max-w-sm">
        <!-- Logo & App Header -->
        <div class="text-center mb-8">
            <div class="inline-flex w-12 h-12 rounded-2xl bg-brand text-white items-center justify-center shadow-md font-bold text-lg tracking-wider mb-3">
                PIB
            </div>
            <h1 class="text-2xl font-bold text-ink tracking-tight">PIB Penilaian</h1>
            <p class="text-xs text-muted mt-1">Aplikasi Penilaian Praktik Ibadah Siswa</p>
        </div>

        <!-- Login Card -->
        <div class="bg-surface border border-line rounded-[14px] p-6 shadow-sm">
            <h2 class="text-base font-semibold text-ink mb-5 text-center">Masuk ke Akun</h2>

            @if ($errors->any())
                <div class="mb-5 p-3 rounded-xl bg-danger-soft border border-red-200 text-xs text-danger font-medium flex items-start gap-2.5">
                    <x-icon name="alert-circle" size="16" class="shrink-0 mt-0.5" />
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            <form method="POST" action="/login" class="space-y-4">
                @csrf

                <x-field label="Alamat Email" id="email" required>
                    <x-input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="nama@sekolah.sch.id"
                        :has-error="$errors->has('email')"
                    />
                </x-field>

                <x-field label="Kata Sandi" id="password" required>
                    <x-input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="••••••••••"
                        :has-error="$errors->has('password')"
                    />
                </x-field>

                <div class="flex items-center justify-between text-xs text-muted pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="rounded border-line text-brand focus:ring-focus">
                        <span>Ingat saya</span>
                    </label>
                </div>

                <div class="pt-2">
                    <x-button type="submit" variant="primary" class="w-full">
                        <span>Masuk</span>
                    </x-button>
                </div>
            </form>
        </div>

        <p class="text-center text-xs text-muted mt-6">
            Lupa kata sandi? Hubungi administrator sekolah.
        </p>
    </div>
</body>
</html>

