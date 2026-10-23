<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-canvas text-ink antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' — ' . config('app.name', 'PIB Penilaian') : config('app.name', 'PIB Penilaian') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <script>
        if (localStorage.getItem('pib-sidebar-collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body
    class="h-full flex flex-col font-sans"
    x-data="{
        sidebarCollapsed: localStorage.getItem('pib-sidebar-collapsed') === 'true',
        mobileDrawerOpen: false,
        desktop: window.innerWidth >= 1024,
        openDrawer() {
            this.mobileDrawerOpen = true;
            this.$nextTick(() => this.$refs.closeDrawer.focus());
        },
        closeDrawer() {
            this.mobileDrawerOpen = false;
            this.$nextTick(() => this.$refs.openDrawer.focus());
        },
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('pib-sidebar-collapsed', this.sidebarCollapsed);
            document.documentElement.classList.toggle('sidebar-collapsed', this.sidebarCollapsed);
        }
    }"
    @resize.window="desktop = window.innerWidth >= 1024"
    @keydown.escape.window="if (mobileDrawerOpen) closeDrawer()"
>
    <!-- Skip link aksesibilitas -->
    <a
        href="#konten"
        class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:px-4 focus:py-2.5 focus:bg-surface focus:rounded-lg focus:shadow-md focus:text-brand-strong focus:border focus:border-brand font-medium text-sm"
    >
        Lewati ke konten
    </a>

    <!-- Mobile Drawer Backdrop -->
    <div
        x-show="mobileDrawerOpen"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 lg:hidden"
        style="display: none;"
        @click="closeDrawer()"
    ></div>

    <div class="flex h-full w-full overflow-hidden">
        <!-- Sidebar Navigation Landmark -->
        <nav
            id="navigasi-utama"
            aria-label="Navigasi Utama"
            :inert="!mobileDrawerOpen && !desktop"
            class="fixed inset-y-0 left-0 z-50 flex flex-col bg-surface border-r border-line transition-all duration-200 lg:static"
            :class="{
                'w-[252px]': !sidebarCollapsed,
                'lg:w-[68px] w-[252px]': sidebarCollapsed,
                'translate-x-0': mobileDrawerOpen,
                '-translate-x-full lg:translate-x-0': !mobileDrawerOpen,
            }"
        >
            <!-- Sidebar Header / Logo -->
            <div class="h-[68px] flex items-center justify-between px-4 border-b border-line shrink-0">
                <a href="/dashboard" class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-9 h-9 rounded-xl bg-brand-strong text-white flex items-center justify-center shrink-0 shadow-xs font-bold text-sm tracking-wider">
                        PIB
                    </div>
                    <div
                        class="flex flex-col whitespace-nowrap transition-opacity duration-150"
                        :class="sidebarCollapsed ? 'lg:hidden' : 'block'"
                    >
                        <span class="font-semibold text-sm text-ink leading-tight">PIB Penilaian</span>
                        <span class="text-[11px] text-muted">Praktik Ibadah</span>
                    </div>
                </a>

                <!-- Mobile close drawer button -->
                <button
                    x-ref="closeDrawer"
                    type="button"
                    class="lg:hidden p-2 text-muted hover:text-ink cursor-pointer rounded-lg"
                    @click="closeDrawer()"
                >
                    <x-icon name="x" size="20" />
                    <span class="sr-only">Tutup menu</span>
                </button>
            </div>

            <!-- Menu Groups -->
            <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
                <!-- Group: Kerja Harian -->
                <div>
                    <div
                        class="px-2.5 mb-2 text-[10px] font-semibold uppercase tracking-wider text-muted/80"
                        :class="sidebarCollapsed ? 'lg:sr-only' : 'block'"
                    >
                        Kerja harian
                    </div>
                    <ul class="space-y-1">
                        <li>
                            <a
                                href="/dashboard"
                                @if(request()->is('dashboard*')) aria-current="page" @endif
                                class="flex items-center gap-3 px-2.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('dashboard*') ? 'bg-brand-soft text-brand-strong' : 'text-ink hover:bg-canvas text-muted hover:text-ink' }}"
                                title="Beranda"
                            >
                                <x-icon name="layout-dashboard" size="18" />
                                <span :class="sidebarCollapsed ? 'lg:hidden' : 'inline'">Beranda</span>
                            </a>
                        </li>
                        <li>
                            <a
                                href="/penilaian"
                                @if(request()->is('penilaian*')) aria-current="page" @endif
                                class="flex items-center gap-3 px-2.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('penilaian*') ? 'bg-brand-soft text-brand-strong' : 'text-ink hover:bg-canvas text-muted hover:text-ink' }}"
                                title="Penilaian"
                            >
                                <x-icon name="clipboard-check" size="18" />
                                <span :class="sidebarCollapsed ? 'lg:hidden' : 'inline'">Penilaian</span>
                            </a>
                        </li>
                        <li>
                            <a
                                href="/rekap"
                                @if(request()->is('rekap*')) aria-current="page" @endif
                                class="flex items-center gap-3 px-2.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('rekap*') ? 'bg-brand-soft text-brand-strong' : 'text-ink hover:bg-canvas text-muted hover:text-ink' }}"
                                title="Rekap"
                            >
                                <x-icon name="bar-chart-3" size="18" />
                                <span :class="sidebarCollapsed ? 'lg:hidden' : 'inline'">Rekap</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Group: Kelola Data -->
                <div>
                    <div
                        class="px-2.5 mb-2 text-[10px] font-semibold uppercase tracking-wider text-muted/80"
                        :class="sidebarCollapsed ? 'lg:sr-only' : 'block'"
                    >
                        Kelola data
                    </div>
                    <ul class="space-y-1">
                        <li>
                            <a
                                href="/sekolah"
                                @if(request()->is('sekolah*')) aria-current="page" @endif
                                class="flex items-center gap-3 px-2.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('sekolah*') ? 'bg-brand-soft text-brand-strong' : 'text-ink hover:bg-canvas text-muted hover:text-ink' }}"
                                title="Sekolah & periode"
                            >
                                <x-icon name="school" size="18" />
                                <span :class="sidebarCollapsed ? 'lg:hidden' : 'inline'">Sekolah & periode</span>
                            </a>
                        </li>
                        <li>
                            <a
                                href="/kelas"
                                @if(request()->is('kelas*')) aria-current="page" @endif
                                class="flex items-center gap-3 px-2.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('kelas*') ? 'bg-brand-soft text-brand-strong' : 'text-ink hover:bg-canvas text-muted hover:text-ink' }}"
                                title="Kelas"
                            >
                                <x-icon name="graduation-cap" size="18" />
                                <span :class="sidebarCollapsed ? 'lg:hidden' : 'inline'">Kelas</span>
                            </a>
                        </li>
                        <li>
                            <a
                                href="/siswa"
                                @if(request()->is('siswa*')) aria-current="page" @endif
                                class="flex items-center gap-3 px-2.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('siswa*') ? 'bg-brand-soft text-brand-strong' : 'text-ink hover:bg-canvas text-muted hover:text-ink' }}"
                                title="Siswa"
                            >
                                <x-icon name="user" size="18" />
                                <span :class="sidebarCollapsed ? 'lg:hidden' : 'inline'">Siswa</span>
                            </a>
                        </li>
                        <li>
                            <a
                                href="/materi"
                                @if(request()->is('materi*')) aria-current="page" @endif
                                class="flex items-center gap-3 px-2.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('materi*') ? 'bg-brand-soft text-brand-strong' : 'text-ink hover:bg-canvas text-muted hover:text-ink' }}"
                                title="Materi"
                            >
                                <x-icon name="book-open" size="18" />
                                <span :class="sidebarCollapsed ? 'lg:hidden' : 'inline'">Materi</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Group: Laporan -->
                <div>
                    <div
                        class="px-2.5 mb-2 text-[10px] font-semibold uppercase tracking-wider text-muted/80"
                        :class="sidebarCollapsed ? 'lg:sr-only' : 'block'"
                    >
                        Laporan
                    </div>
                    <ul class="space-y-1">
                        <li>
                            <a
                                href="/laporan"
                                @if(request()->is('laporan*')) aria-current="page" @endif
                                class="flex items-center gap-3 px-2.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('laporan*') ? 'bg-brand-soft text-brand-strong' : 'text-ink hover:bg-canvas text-muted hover:text-ink' }}"
                                title="Laporan & backup"
                            >
                                <x-icon name="file-text" size="18" />
                                <span :class="sidebarCollapsed ? 'lg:hidden' : 'inline'">Laporan & backup</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Group: Admin (Khusus Admin) -->
                @can('admin')
                    <div>
                        <div
                            class="px-2.5 mb-2 text-[10px] font-semibold uppercase tracking-wider text-muted/80"
                            :class="sidebarCollapsed ? 'lg:sr-only' : 'block'"
                        >
                            Admin
                        </div>
                        <ul class="space-y-1">
                            <li>
                                <a
                                    href="/admin/pengguna"
                                    @if(request()->is('admin/pengguna*')) aria-current="page" @endif
                                    class="flex items-center gap-3 px-2.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('admin/pengguna*') ? 'bg-brand-soft text-brand-strong' : 'text-ink hover:bg-canvas text-muted hover:text-ink' }}"
                                    title="Pengguna"
                                >
                                    <x-icon name="user-cog" size="18" />
                                    <span :class="sidebarCollapsed ? 'lg:hidden' : 'inline'">Pengguna</span>
                                </a>
                            </li>
                            <li>
                                <a
                                    href="/admin/audit"
                                    @if(request()->is('admin/audit*')) aria-current="page" @endif
                                    class="flex items-center gap-3 px-2.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('admin/audit*') ? 'bg-brand-soft text-brand-strong' : 'text-ink hover:bg-canvas text-muted hover:text-ink' }}"
                                    title="Audit"
                                >
                                    <x-icon name="history" size="18" />
                                    <span :class="sidebarCollapsed ? 'lg:hidden' : 'inline'">Audit</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                @endcan
            </div>

            <!-- Sidebar Footer (Desktop collapse toggle) -->
            <div class="hidden lg:flex items-center justify-between p-3 border-t border-line shrink-0">
                <button
                    type="button"
                    class="w-full flex items-center gap-2.5 px-2 py-1.5 text-xs text-muted hover:text-ink hover:bg-canvas rounded-lg cursor-pointer"
                    @click="toggleSidebar()"
                    title="Perkecil / Perlebar Menu"
                >
                    <x-icon name="chevron-left" size="16" class="transition-transform duration-200" ::class="sidebarCollapsed ? 'rotate-180' : ''" />
                    <span :class="sidebarCollapsed ? 'lg:hidden' : 'inline'">Ciutkan menu</span>
                </button>
            </div>
        </nav>

        <!-- Main Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto" :inert="mobileDrawerOpen && !desktop">
            <!-- Topbar (68px, Sticky Glass) -->
            <header class="sticky top-0 z-30 h-[68px] bg-white/85 backdrop-blur-md border-b border-line px-4 md:px-8 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <button
                        x-ref="openDrawer"
                        type="button"
                        class="lg:hidden p-2 text-muted hover:text-ink rounded-lg cursor-pointer"
                        @click="openDrawer()"
                        :aria-expanded="mobileDrawerOpen.toString()"
                        aria-controls="navigasi-utama"
                    >
                        <x-icon name="menu" size="20" />
                        <span class="sr-only">Buka menu</span>
                    </button>

                    <div class="flex items-center gap-2 truncate">
                        @php
                            $currentSchoolName = app(\App\Repositories\SettingsRepository::class)->schoolName();
                            $activeAcademicYear = app(\App\Services\AcademicYearService::class)->active();
                        @endphp
                        <span class="font-medium text-sm text-ink truncate">{{ $currentSchoolName ?: 'PIB Penilaian' }}</span>
                        <span class="text-line">/</span>
                        <span class="text-xs text-muted truncate">{{ $activeAcademicYear ? $activeAcademicYear->label() : 'Periode Aktif' }}</span>
                    </div>
                </div>

                <!-- Right Topbar Items -->
                <div class="flex items-center gap-4 shrink-0">
                    <!-- Global Draft Indicator -->
                    <div
                        x-data="draftIndicatorComponent()"
                        x-show="count > 0"
                        style="display: none;"
                        class="flex items-center"
                    >
                        <a
                            href="/penilaian"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-300 hover:bg-amber-100 transition-colors shadow-2xs"
                            title="Klik untuk membuka antrean nilai"
                        >
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            <span x-text="count + ' nilai belum terkirim'"></span>
                        </a>
                    </div>

                    <!-- User Profile Dropdown / Links -->
                    <div class="flex items-center gap-3 pl-3 border-l border-line" x-data="{ userMenuOpen: false }" @keydown.escape="userMenuOpen = false; $refs.userTrigger.focus()">
                        <div class="relative">
                            <button
                                x-ref="userTrigger"
                                type="button"
                                class="flex items-center gap-2 text-sm text-ink hover:text-brand-strong cursor-pointer rounded-lg p-1"
                                @click="userMenuOpen = !userMenuOpen"
                                @click.away="userMenuOpen = false"
                                :aria-expanded="userMenuOpen.toString()"
                                aria-controls="menu-pengguna"
                                aria-label="Menu akun pengguna"
                            >
                                <span class="w-8 h-8 rounded-full bg-brand-soft text-brand-strong font-semibold flex items-center justify-center text-xs">
                                    {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                                </span>
                                <span class="hidden md:inline font-medium text-xs">{{ auth()->user()->name ?? 'Pengguna' }}</span>
                                <x-icon name="chevron-down" size="14" class="text-muted" />
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                id="menu-pengguna"
                                x-show="userMenuOpen"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                style="display: none;"
                                class="absolute right-0 mt-2 w-48 rounded-xl bg-surface border border-line shadow-md py-1.5 text-xs z-50"
                            >
                                <div class="px-3 py-2 border-b border-line text-muted">
                                    <div class="font-medium text-ink truncate">{{ auth()->user()->name ?? '' }}</div>
                                    <div class="text-[11px] truncate">{{ auth()->user()->email ?? '' }}</div>
                                </div>
                                <a href="/akun" class="flex items-center gap-2 px-3 py-2 hover:bg-canvas text-ink">
                                    <x-icon name="lock" size="14" />
                                    <span>Akun & Kata Sandi</span>
                                </a>
                                <form method="POST" action="/logout">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-danger hover:bg-danger-soft text-left cursor-pointer">
                                        <x-icon name="log-out" size="14" />
                                        <span>Keluar</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Landmark -->
            <main id="konten" class="flex-1 p-4 md:px-9 md:py-8.5 max-w-[1320px] w-full mx-auto focus:outline-none" tabindex="-1">
                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <x-toast />

    @livewireScripts
</body>
</html>

