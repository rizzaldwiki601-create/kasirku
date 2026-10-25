@extends('layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Ringkasan')
@section('content')
    <div class="page-heading dashboard-heading"><div><span class="eyebrow">RINGKASAN TOKO</span><h1>Selamat beraktivitas, {{ auth()->user()->name }}.</h1><p>{{ now()->translatedFormat('l, d F Y') }} <span class="date-separator">·</span> Pantau kegiatan toko dari sini.</p></div><a class="button button-primary" href="{{ route('sales.pos') }}"><span aria-hidden="true">＋</span> Transaksi baru</a></div>
    <section class="dashboard-overview" aria-label="Ringkasan penjualan">
        <article class="revenue-feature">
            <div class="revenue-feature-top"><span class="feature-label">PENDAPATAN HARI INI</span><span class="live-indicator">Hari ini</span></div>
            <strong>Rp {{ number_format((float) $todayRevenue, 2, ',', '.') }}</strong>
            <p>Akumulasi transaksi tersimpan hari ini</p>
            <div class="revenue-feature-bottom"><span>{{ number_format($saleCount) }} transaksi tercatat</span><a href="{{ route('sales.index') }}">Lihat transaksi <span aria-hidden="true">→</span></a></div>
        </article>
        <div class="overview-support">
            @if (auth()->user()->role === 'admin')
                <article class="overview-stat"><span class="overview-stat-label">Pendapatan bulan ini</span><strong>Rp {{ number_format((float) $monthRevenue, 2, ',', '.') }}</strong><small>{{ now()->translatedFormat('F Y') }}</small></article>
                <article class="overview-stat"><span class="overview-stat-label">Seluruh transaksi</span><strong>{{ number_format($saleCount) }}</strong><small>Data penjualan tersimpan</small></article>
            @else
                <article class="overview-stat"><span class="overview-stat-label">Pendapatan bulan ini</span><strong>Rp {{ number_format((float) $monthRevenue, 2, ',', '.') }}</strong><small>{{ now()->translatedFormat('F Y') }}</small></article>
                <article class="overview-stat"><span class="overview-stat-label">Transaksi Anda</span><strong>{{ number_format($saleCount) }}</strong><small>Transaksi yang Anda catat</small></article>
            @endif
        </div>
    </section>
    @if (auth()->user()->role === 'admin')
        <section class="admin-metrics" aria-label="Ringkasan inventaris">
            <article class="metric-compact"><span class="metric-mark metric-mark-dark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/></svg></span><div><span>Produk</span><strong>{{ number_format($productCount) }}</strong></div><a href="{{ route('products.index') }}" aria-label="Kelola produk">→</a></article>
            <article class="metric-compact"><span class="metric-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/></svg></span><div><span>Kategori</span><strong>{{ number_format($categoryCount) }}</strong></div><a href="{{ route('categories.index') }}" aria-label="Kelola kategori">→</a></article>
            <article class="metric-compact metric-warning"><span class="metric-mark metric-mark-warm" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v5m0 3h.01"/></svg></span><div><span>Stok perlu dicek</span><strong>{{ number_format($lowStockCount) }}</strong></div><a href="{{ route('products.index') }}" aria-label="Periksa stok produk">→</a></article>
        </section>
        <div class="dashboard-columns">
            <section class="panel stock-panel">
                <div class="panel-heading"><div><span class="eyebrow">PERSEDIAAN</span><h2>Perhatian stok</h2><p>Produk yang sudah mencapai batas minimum.</p></div><a class="text-link" href="{{ route('products.index') }}">Semua produk <span aria-hidden="true">→</span></a></div>
                @if ($lowStockProducts->isEmpty())
                    <div class="empty-state compact-empty"><span class="empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg></span><strong>Stok dalam kondisi baik</strong><p>Tidak ada produk yang mencapai batas minimum.</p></div>
                @else
                    <div class="stock-list">
                        @foreach ($lowStockProducts as $product)
                            <a class="stock-row" href="{{ route('products.show', $product) }}"><span class="stock-product-mark" aria-hidden="true">{{ strtoupper(substr($product->name, 0, 1)) }}</span><span class="stock-product-copy"><strong>{{ $product->name }}</strong><small>{{ $product->category->name }}</small></span><span class="stock-quantity {{ $product->stock === 0 ? 'stock-empty' : '' }}"><strong>{{ number_format($product->stock) }}</strong><small>{{ $product->unit }} tersisa</small></span></a>
                        @endforeach
                    </div>
                @endif
            </section>
            <section class="panel latest-panel">
                <div class="panel-heading"><div><span class="eyebrow">AKTIVITAS</span><h2>Transaksi terbaru</h2><p>Penjualan yang baru saja tercatat.</p></div><a class="text-link" href="{{ route('sales.index') }}">Lihat semua <span aria-hidden="true">→</span></a></div>
                @if ($latestSales->isEmpty())
                    <div class="empty-state compact-empty"><span class="empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16M4 12h16M4 19h16"/></svg></span><strong>Belum ada transaksi</strong><p>Transaksi yang selesai akan tampil di sini.</p><a class="button button-primary" href="{{ route('sales.pos') }}">Mulai transaksi</a></div>
                @else
                    <div class="table-wrap"><table><thead><tr><th>No. transaksi</th><th>Kasir</th><th>Tanggal</th><th class="align-right">Total</th></tr></thead><tbody>
                        @foreach ($latestSales as $sale)<tr><td><a class="text-link" href="{{ route('sales.show', $sale) }}">{{ $sale->invoice_number }}</a></td><td>{{ $sale->user?->name ?? 'Pengguna dihapus' }}</td><td>{{ $sale->sold_at->format('d/m/Y H:i') }}</td><td class="align-right">Rp {{ number_format((float) $sale->total, 2, ',', '.') }}</td></tr>@endforeach
                    </tbody></table></div>
                @endif
            </section>
        </div>
    @else
        <section class="panel cashier-activity">
            <div class="panel-heading"><div><span class="eyebrow">AKTIVITAS</span><h2>Transaksi terbaru Anda</h2><p>Riwayat penjualan yang Anda catat.</p></div><a class="text-link" href="{{ route('sales.index') }}">Lihat semua <span aria-hidden="true">→</span></a></div>
            @if ($latestSales->isEmpty())
                <div class="empty-state"><strong>Belum ada transaksi</strong><p>Transaksi yang selesai akan tampil di sini.</p><a class="button button-primary" href="{{ route('sales.pos') }}">Mulai transaksi</a></div>
            @else
                <div class="table-wrap"><table><thead><tr><th>No. transaksi</th><th>Tanggal</th><th>Metode</th><th class="align-right">Total</th></tr></thead><tbody>
                    @foreach ($latestSales as $sale)<tr><td><a class="text-link" href="{{ route('sales.show', $sale) }}">{{ $sale->invoice_number }}</a></td><td>{{ $sale->sold_at->format('d/m/Y H:i') }}</td><td><span class="badge">{{ strtoupper($sale->payment_method) }}</span></td><td class="align-right">Rp {{ number_format((float) $sale->total, 2, ',', '.') }}</td></tr>@endforeach
                </tbody></table></div>
            @endif
        </section>
    @endif
@endsection
