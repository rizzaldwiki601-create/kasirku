@extends('layouts.app')
@section('title', 'Riwayat transaksi')
@section('heading', 'Transaksi')
@section('content')
    <div class="page-heading"><div><h1>Riwayat transaksi</h1><p>Lihat transaksi penjualan yang tersimpan.</p></div><a class="button button-primary" href="{{ route('sales.pos') }}">＋ Transaksi baru</a></div>
    <section class="panel">
        <form class="toolbar" method="GET"><label class="search-field"><span>⌕</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Nomor transaksi"></label><label class="date-field">Dari<input type="date" name="start_date" value="{{ request('start_date') }}"></label><label class="date-field">Sampai<input type="date" name="end_date" value="{{ request('end_date') }}"></label><button class="button button-secondary" type="submit">Filter</button><a class="button button-quiet" href="{{ route('sales.index') }}">Reset</a></form>
        @if($sales->isEmpty())<div class="empty-state"><span class="empty-icon">◷</span><strong>Transaksi tidak ditemukan</strong><p>Transaksi yang cocok dengan filter akan tampil di sini.</p></div>
        @else
            <div class="table-wrap"><table><thead><tr><th>No. transaksi</th><th>Tanggal</th><th>Kasir</th><th>Jumlah barang</th><th>Metode</th><th class="align-right">Total</th><th></th></tr></thead><tbody>
                @foreach($sales as $sale)<tr><td><a class="text-link" href="{{ route('sales.show', $sale) }}">{{ $sale->invoice_number }}</a></td><td>{{ $sale->sold_at->format('d/m/Y H:i') }}</td><td>{{ $sale->user?->name ?? 'Pengguna dihapus' }}</td><td>{{ $sale->items_sum_quantity ?? 0 }}</td><td><span class="badge">{{ strtoupper($sale->payment_method) }}</span></td><td class="align-right">Rp {{ number_format((float) $sale->total, 2, ',', '.') }}</td><td class="align-right"><a class="button button-secondary button-small" href="{{ route('sales.receipt', $sale) }}">Struk</a></td></tr>@endforeach
            </tbody></table></div><div class="pagination-wrap">{{ $sales->links() }}</div>
        @endif
    </section>
@endsection
