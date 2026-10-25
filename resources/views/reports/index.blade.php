@extends('layouts.app')
@section('title', 'Laporan penjualan')
@section('heading', 'Laporan')
@section('content')
    <div class="page-heading"><div><h1>Laporan penjualan</h1><p>Ringkasan penjualan berdasarkan periode tanggal.</p></div><button class="button button-secondary no-print" type="button" onclick="window.print()">Cetak laporan</button></div>
    <section class="panel no-print"><form class="toolbar" method="GET"><label class="date-field">Dari<input type="date" name="start_date" value="{{ request('start_date') }}"></label><label class="date-field">Sampai<input type="date" name="end_date" value="{{ request('end_date') }}"></label><button class="button button-primary" type="submit">Terapkan filter</button><a class="button button-quiet" href="{{ route('reports.index') }}">Reset</a></form></section>
    <div class="stats-grid report-stats">
        <article class="stat-card"><div><span>Jumlah transaksi</span><strong>{{ number_format($transactionCount) }}</strong></div></article>
        <article class="stat-card"><div><span>Total pendapatan</span><strong>Rp {{ number_format((float) $revenue, 2, ',', '.') }}</strong></div></article>
        <article class="stat-card"><div><span>Barang terjual</span><strong>{{ number_format($itemsSold) }}</strong></div></article>
        <article class="stat-card"><div><span>Estimasi laba kotor</span><strong>Rp {{ number_format((float) $estimatedProfit, 2, ',', '.') }}</strong><small>Harga jual dikurangi harga beli pada saat transaksi</small></div></article>
    </div>
    <section class="panel">
        <div class="panel-heading"><div><h2>Penjualan per hari</h2><p>Jumlah transaksi dan pendapatan per tanggal</p></div></div>
        @if($dailySales->isEmpty())<div class="empty-state"><strong>Belum ada penjualan pada periode ini</strong></div>@else
            <div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Jumlah transaksi</th><th class="align-right">Pendapatan</th></tr></thead><tbody>@foreach($dailySales as $day)<tr><td>{{ \Illuminate\Support\Carbon::parse($day->sale_date)->format('d/m/Y') }}</td><td>{{ $day->transaction_count }}</td><td class="align-right">Rp {{ number_format((float) $day->revenue, 2, ',', '.') }}</td></tr>@endforeach</tbody></table></div>
        @endif
    </section>
    <section class="panel">
        <div class="panel-heading"><div><h2>Produk terlaris</h2><p>Diurutkan berdasarkan jumlah terjual</p></div></div>
        @if($topProducts->isEmpty())<div class="empty-state"><strong>Belum ada data produk terjual</strong></div>@else
            <div class="table-wrap"><table><thead><tr><th>Nama produk</th><th>Jumlah terjual</th><th class="align-right">Pendapatan</th></tr></thead><tbody>@foreach($topProducts as $product)<tr><td>{{ $product->product_name }}</td><td>{{ $product->quantity_sold }}</td><td class="align-right">Rp {{ number_format((float) $product->revenue, 2, ',', '.') }}</td></tr>@endforeach</tbody></table></div>
        @endif
    </section>
    <section class="panel">
        <div class="panel-heading"><div><h2>Daftar transaksi</h2><p>Rincian transaksi untuk periode terpilih</p></div></div>
        @if($transactions->isEmpty())<div class="empty-state"><strong>Tidak ada transaksi pada periode ini</strong></div>@else
            <div class="table-wrap"><table><thead><tr><th>No. transaksi</th><th>Tanggal</th><th>Kasir</th><th>Metode</th><th class="align-right">Total</th></tr></thead><tbody>@foreach($transactions as $sale)<tr><td><a class="text-link" href="{{ route('sales.show', $sale) }}">{{ $sale->invoice_number }}</a></td><td>{{ $sale->sold_at->format('d/m/Y H:i') }}</td><td>{{ $sale->user?->name ?? 'Pengguna dihapus' }}</td><td>{{ strtoupper($sale->payment_method) }}</td><td class="align-right">Rp {{ number_format((float) $sale->total, 2, ',', '.') }}</td></tr>@endforeach</tbody></table></div><div class="pagination-wrap">{{ $transactions->links() }}</div>
        @endif
    </section>
@endsection
