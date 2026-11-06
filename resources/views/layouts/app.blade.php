<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SOSPEM — Pemantauan Opini Publik & Jaringan Bot')</title>

    <!-- Inisialisasi Tema Sejak Awal (Tanpa Kedipan Saat Pindah Halaman) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('sospem_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <!-- Tipografi Tunggal: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ==========================================================================
           DESIGN TOKENS:
           1. Dark Mode = Stockbit Dark Mode (#121212 & #181818 Carbon Matte)
           2. Light Mode = Clean Light Mode (#f8fafc & #ffffff)
           ========================================================================== */
        :root,
        html[data-theme="dark"] {
            --color-canvas: #121212;
            --color-surface: #181818;
            --color-surface-subtle: #1f1f1f;
            --color-surface-hover: #252525;
            --color-surface-pill: #2a2a2a;

            --color-border: #2b2b2b;
            --color-border-subtle: #222222;
            --color-border-strong: #3d3d3d;

            --color-text-primary: #f5f5f5;
            --color-text-secondary: #9e9e9e;
            --color-text-muted: #6e6e6e;

            --color-teal: #18b26f;
            --color-teal-dark: #21ce83;
            --color-teal-bg: rgba(24, 178, 111, 0.15);
            --color-teal-border: rgba(24, 178, 111, 0.35);

            --color-red: #f04438;
            --color-red-dark: #f95950;
            --color-red-bg: rgba(240, 68, 56, 0.15);
            --color-red-border: rgba(240, 68, 56, 0.35);

            --color-slate-bar: #2a2a2a;
            --color-slate-dark: #525252;

            --radius-xs: 4px;
            --radius-sm: 5px;
            --radius-md: 6px;
            --radius-lg: 8px;

            --shadow-card: none;
            --shadow-modal: 0 20px 40px rgba(0, 0, 0, 0.75);
        }

        html[data-theme="light"] {
            --color-canvas: #f8fafc;
            --color-surface: #ffffff;
            --color-surface-subtle: #f8fafc;
            --color-surface-hover: #f1f5f9;
            --color-surface-pill: #f1f5f9;

            --color-border: #e2e8f0;
            --color-border-subtle: #f1f5f9;
            --color-border-strong: #cbd5e1;

            --color-text-primary: #0f172a;
            --color-text-secondary: #64748b;
            --color-text-muted: #94a3b8;

            --color-teal: #10b981;
            --color-teal-dark: #059669;
            --color-teal-bg: #ecfdf5;
            --color-teal-border: #a7f3d0;

            --color-red: #e11d48;
            --color-red-dark: #be123c;
            --color-red-bg: #fff1f2;
            --color-red-border: #fecdd3;

            --color-slate-bar: #cbd5e1;
            --color-slate-dark: #334155;

            --radius-xs: 4px;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 12px;

            --shadow-card: 0 1px 2px rgba(15, 23, 42, 0.03);
            --shadow-modal: 0 20px 36px -8px rgba(15, 23, 42, 0.14);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--color-canvas);
            color: var(--color-text-primary);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum";
        }

        a:focus-visible, button:focus-visible, input:focus-visible {
            outline: 2px solid var(--color-text-primary);
            outline-offset: 2px;
        }

        /* ==========================================================================
           APP SHELL & SIDEBAR (MINIMIZE / MAXIMIZE)
           ========================================================================== */
        .app-shell {
            display: flex;
            min-height: 100vh;
            background-color: var(--color-canvas);
        }

        .left-sidebar {
            width: 240px;
            border-right: 1px solid var(--color-border);
            background-color: var(--color-surface);
            display: flex;
            flex-direction: column;
            padding: 16px 12px;
            position: sticky;
            top: 0;
            height: 100vh;
            flex-shrink: 0;
            z-index: 50;
            transition: width 0.2s cubic-bezier(0.4, 0, 0.2, 1), padding 0.2s ease;
            overflow-x: hidden;
        }

        /* State Minimize: Mepet ke kiri, hanya terlihat ikon/logo */
        .left-sidebar.is-minimized {
            width: 64px;
            padding: 16px 10px;
        }

        .sidebar-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            min-height: 38px;
        }

        .left-sidebar.is-minimized .sidebar-top-row {
            justify-content: center;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--color-text-primary);
            min-width: 0;
        }

        .left-sidebar.is-minimized .brand-link {
            display: none;
        }

        .brand-logo-box {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background-color: var(--color-surface-pill);
            border: 1px solid var(--color-border);
            color: var(--color-text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-weight: 700;
            font-size: 13px;
        }

        .brand-text-wrap {
            display: flex;
            flex-direction: column;
            white-space: nowrap;
            overflow: hidden;
        }

        .brand-title {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: -0.01em;
            line-height: 1.2;
        }

        .brand-sub {
            font-size: 11px;
            color: var(--color-text-muted);
        }

        .toggle-sidebar-btn {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid var(--color-border);
            background: var(--color-surface-subtle);
            color: var(--color-text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: background 0.15s, color 0.15s;
        }
        .toggle-sidebar-btn:hover {
            background: var(--color-surface-subtle);
            color: var(--color-text-primary);
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 100%;
        }

        .side-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            height: 40px;
            padding: 0 11px;
            border-radius: 8px;
            border: 1px solid transparent;
            text-decoration: none;
            color: var(--color-text-secondary);
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            transition: all 0.15s ease;
        }
        .side-nav-link:hover {
            color: var(--color-text-primary);
            background-color: var(--color-surface-subtle);
        }
        .side-nav-link.is-active {
            color: var(--color-teal-dark);
            background-color: rgba(24, 178, 111, 0.1);
            border-color: rgba(24, 178, 111, 0.25);
            font-weight: 600;
        }

        .side-nav-icon {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .side-nav-label {
            opacity: 1;
            transition: opacity 0.15s ease;
        }

        .left-sidebar.is-minimized .side-nav-link {
            justify-content: center;
            padding: 0;
        }

        .left-sidebar.is-minimized .side-nav-label {
            display: none;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid var(--color-border-subtle);
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .theme-toggle-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            height: 40px;
            padding: 0 11px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid var(--color-border);
            background-color: var(--color-surface-subtle);
            color: var(--color-text-secondary);
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .theme-toggle-btn:hover {
            color: var(--color-text-primary);
            background-color: var(--color-surface-hover);
            border-color: var(--color-border-strong);
        }

        .left-sidebar.is-minimized .theme-toggle-btn {
            justify-content: center;
            padding: 0;
        }

        .left-sidebar.is-minimized .theme-toggle-label {
            display: none;
        }

        /* Area Konten Utama */
        .main-workspace {
            flex: 1;
            width: 100%;
            min-width: 0;
            max-width: 1360px;
            margin: 0 auto;
            padding: 26px 36px 64px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .main-workspace {
                padding: 18px 16px 48px;
            }
        }

        /* Header Baris Judul Atas */
        .page-title-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-heading {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .page-heading-muted {
            font-weight: 400;
            color: var(--color-text-secondary);
        }

        .top-right-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-clean {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--color-border);
            background: var(--color-surface);
            color: var(--color-text-primary);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-clean:hover {
            background: var(--color-surface-hover);
            border-color: var(--color-border-strong);
        }
        .btn-clean.btn-primary {
            background: var(--color-teal);
            color: #ffffff;
            border-color: var(--color-teal);
            font-weight: 600;
        }
        .btn-clean.btn-primary:hover {
            background: #149a5f;
        }

        /* ==========================================================================
           BADGE STATUS TREN:
           - Naik: Hijau Mint Teal (.is-up)
           - Stabil: Abu-abu (.is-stable)
           - Turun: Merah (.is-down)
           (Tanpa indikator "Naik Cepat")
           ========================================================================== */
        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 6px;
            white-space: nowrap;
        }
        /* Naik: Hijau Mint */
        .status-pill.is-up {
            background-color: var(--color-teal-bg);
            color: var(--color-teal-dark);
            border: 1px solid var(--color-teal-border);
        }
        /* Stabil: Abu-abu */
        .status-pill.is-stable {
            background-color: var(--color-surface-pill);
            color: var(--color-text-secondary);
            border: 1px solid var(--color-border);
        }
        /* Turun: Merah */
        .status-pill.is-down {
            background-color: var(--color-red-bg);
            color: var(--color-red-dark);
            border: 1px solid var(--color-red-border);
        }

        /* Toast Notifikasi */
        .app-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #222222;
            border: 1px solid var(--color-border-strong);
            color: #ffffff;
            padding: 10px 16px;
            border-radius: var(--radius-sm);
            font-size: 12.5px;
            font-weight: 500;
            display: none;
            box-shadow: var(--shadow-modal);
            z-index: 2000;
            align-items: center;
            gap: 8px;
        }
        .app-toast.is-active {
            display: flex;
        }
    </style>

    @yield('styles')
</head>
<body>

    <div class="app-shell">

        <!-- SIDEBAR KIRI: BISA MINIMIZE (LOGO SAJA) & MAXIMIZE (LOGO + TEKS) -->
        <aside class="left-sidebar" id="appSidebar" aria-label="Navigasi Utama">
            <div class="sidebar-top-row">
                <a href="{{ route('dashboard') }}" class="brand-link">
                    <div class="brand-logo-box">S</div>
                    <div class="brand-text-wrap">
                        <span class="brand-title">SOSPEM</span>
                        <span class="brand-sub">Opini Publik &amp; Bot</span>
                    </div>
                </a>

                <button type="button" class="toggle-sidebar-btn" onclick="toggleSidebar()" title="Minimize / Maximize Sidebar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                    </svg>
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="side-nav-link {{ request()->routeIs('dashboard') || request()->routeIs('feed.platform') ? 'is-active' : '' }}" title="Dashboard Utama">
                    <svg class="side-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                        <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                        <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                        <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                    </svg>
                    <span class="side-nav-label">Dashboard Isu</span>
                </a>

                <a href="{{ route('issues.index') }}" class="side-nav-link {{ request()->routeIs('issues.*') ? 'is-active' : '' }}" title="Tambah & Kelola Isu Pantauan">
                    <svg class="side-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span class="side-nav-label">Kelola Isu</span>
                </a>

                <a href="{{ route('accounts') }}" class="side-nav-link {{ request()->routeIs('accounts') ? 'is-active' : '' }}" title="Audit Profil & Jaringan Akun">
                    <svg class="side-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span class="side-nav-label">Profil &amp; Audit Bot</span>
                </a>

                <a href="{{ route('analytics') }}" class="side-nav-link {{ request()->routeIs('analytics') ? 'is-active' : '' }}" title="Grafik Deret Waktu & Analitik">
                    <svg class="side-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span class="side-nav-label">Grafik Analitik</span>
                </a>

                <a href="{{ route('methodology') }}" class="side-nav-link {{ request()->routeIs('methodology') ? 'is-active' : '' }}" title="Metodologi & Kepatuhan UU PDP">
                    <svg class="side-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    <span class="side-nav-label">Metodologi &amp; UU PDP</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <button type="button" class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleTheme()" title="Ganti Mode Malam / Mode Terang">
                    <!-- Ikon Matahari (Tampil saat Dark Mode untuk beralih ke Light Mode) -->
                    <svg id="themeIconSun" class="side-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                    <!-- Ikon Bulan (Tampil saat Light Mode untuk beralih ke Dark Mode) -->
                    <svg id="themeIconMoon" class="side-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                    <span class="theme-toggle-label" id="themeToggleLabel">Mode Terang</span>
                </button>
            </div>
        </aside>

        <!-- AREA KONTEN UTAMA -->
        <div class="main-workspace">
            @yield('content')
        </div>

    </div>

    <!-- TOAST NOTIFIKASI -->
    <div class="app-toast" id="toastNotif">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <span id="toastText">Data stream baru berhasil diterima</span>
    </div>

    <script>
        // Restore sidebar state & theme UI saat halaman dimuat
        function syncThemeButtonUI() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            const sunIcon = document.getElementById('themeIconSun');
            const moonIcon = document.getElementById('themeIconMoon');
            const label = document.getElementById('themeToggleLabel');

            if (currentTheme === 'dark') {
                sunIcon.style.display = 'block';
                moonIcon.style.display = 'none';
                label.innerText = 'Mode Terang';
            } else {
                sunIcon.style.display = 'none';
                moonIcon.style.display = 'block';
                label.innerText = 'Mode Malam';
            }
        }

        (function() {
            const sidebar = document.getElementById('appSidebar');
            const savedState = localStorage.getItem('sospem_sidebar_minimized');
            if (savedState === 'true') {
                sidebar.classList.add('is-minimized');
            }
            syncThemeButtonUI();
        })();

        function toggleSidebar() {
            const sidebar = document.getElementById('appSidebar');
            sidebar.classList.toggle('is-minimized');
            localStorage.setItem('sospem_sidebar_minimized', sidebar.classList.contains('is-minimized'));
        }

        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme') || 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('sospem_theme', next);
            syncThemeButtonUI();
            window.dispatchEvent(new CustomEvent('sospem-theme-changed', { detail: { theme: next } }));
        }

        function showToast(msg) {
            const t = document.getElementById('toastNotif');
            document.getElementById('toastText').innerText = msg;
            t.classList.add('is-active');
            setTimeout(() => { t.classList.remove('is-active'); }, 2500);
        }
    </script>

    @yield('scripts')
</body>
</html>

