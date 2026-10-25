<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'KasirKu') | KasirKu</title>
    <link rel="stylesheet" href="{{ asset('css/kasirku.css') }}">
</head>
<body class="dashboard-body">
    <div class="mobile-overlay" data-sidebar-close></div>
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark" aria-hidden="true">K</span><span>KASIRKU<small>Retail operations</small></span></a>
        <nav class="nav-menu" aria-label="Navigasi utama">
            <span class="nav-label">OPERASIONAL</span>
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1z"/></svg></span>Ringkasan</a>
            <a class="nav-link {{ request()->routeIs('sales.pos') ? 'active' : '' }}" href="{{ route('sales.pos') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h3m4 0h3m-10 4h3m4 0h3"/></svg></span>Kasir</a>
            <a class="nav-link {{ request()->routeIs('sales.*') && !request()->routeIs('sales.pos') ? 'active' : '' }}" href="{{ route('sales.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16M4 12h16M4 19h16"/><circle cx="8" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="10" cy="19" r="1"/></svg></span>Transaksi</a>
            @if (auth()->user()->role === 'admin')
                <span class="nav-label nav-label-spaced">MANAJEMEN</span>
                <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/></svg></span>Produk</a>
                <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/></svg></span>Kategori</a>
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0m2-13h5m-2.5-2.5v5"/></svg></span>Pengguna</a>
                <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20V10m5 10V4m5 16v-7m5 7V7M2 21h20"/></svg></span>Laporan</a>
            @endif
        </nav>
        <div class="sidebar-foot"><span class="sidebar-foot-mark"></span><span><strong>Operasional toko</strong><small>Data tersimpan dengan aman</small></span></div>
    </aside>
    <main class="main-shell">
        <header class="topbar">
            <button class="menu-toggle" type="button" aria-label="Buka navigasi" aria-controls="sidebar" aria-expanded="false" data-sidebar-toggle><span></span><span></span><span></span></button>
            <div class="topbar-title"><span class="topbar-context">KASIRKU <span>/</span></span>@yield('heading', 'Ringkasan')</div>
            <div class="topbar-user">
                <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="user-label"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Kasir' }}</small></span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="button button-quiet button-small" type="submit">Keluar</button></form>
            </div>
        </header>
        <section class="page-content">
            @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
            @if (session('error'))<div class="alert alert-error" role="alert">{{ session('error') }}</div>@endif
            @if ($errors->any())
                <div class="alert alert-error" role="alert"><strong>Periksa kembali data yang dimasukkan.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @yield('content')
        </section>
    </main>
    <script>
        const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
        const closeSidebar = () => {
            document.body.classList.remove('sidebar-open');
            sidebarToggle?.setAttribute('aria-expanded', 'false');
        };
        sidebarToggle?.addEventListener('click', () => {
            const isOpen = document.body.classList.toggle('sidebar-open');
            sidebarToggle.setAttribute('aria-expanded', String(isOpen));
        });
        document.querySelectorAll('[data-sidebar-close], .nav-link').forEach((item) => item.addEventListener('click', closeSidebar));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeSidebar();
        });
    </script>
    @stack('scripts')
</body>
</html>
