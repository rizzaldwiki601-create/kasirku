@extends('layouts.app')
@section('title', 'Detail transaksi')
@section('heading', 'Transaksi')
@section('content')
    <div class="page-heading"><div><h1>Detail transaksi</h1><p>{{ $sale->invoice_number }}</p></div><div class="heading-actions"><a class="button button-secondary" href="{{ route('sales.index') }}">← Kembali</a><a class="button button-primary" href="{{ route('sales.receipt', $sale) }}">Lihat / cetak struk</a></div></div>
    <section class="panel">
        <dl class="detail-grid sale-meta"><div><dt>Nomor transaksi</dt><dd>{{ $sale->invoice_number }}</dd></div><div><dt>Tanggal</dt><dd>{{ $sale->sold_at->format('d/m/Y H:i:s') }}</dd></div><div><dt>Kasir</dt><dd>{{ $sale->user?->name ?? 'Pengguna dihapus' }}</dd></div><div><dt>Metode pembayaran</dt><dd>{{ strtoupper($sale->payment_method) }}</dd></div><div><dt>Nominal dibayar</dt><dd>Rp {{ number_format((float) $sale->amount_paid, 2, ',', '.') }}</dd></div><div><dt>Kembalian</dt><dd>Rp {{ number_format((float) $sale->change_amount, 2, ',', '.') }}</dd></div></dl>
        <div class="table-wrap"><table><thead><tr><th>Nama produk</th><th>Harga satuan</th><th>Jumlah</th><th class="align-right">Subtotal</th></tr></thead><tbody>@foreach($sale->items as $item)<tr><td>{{ $item->product_name }}</td><td>Rp {{ number_format((float) $item->unit_price, 2, ',', '.') }}</td><td>{{ $item->quantity }}</td><td class="align-right">Rp {{ number_format((float) $item->subtotal, 2, ',', '.') }}</td></tr>@endforeach</tbody><tfoot><tr><th colspan="3">Total</th><th class="align-right">Rp {{ number_format((float) $sale->total, 2, ',', '.') }}</th></tr></tfoot></table></div>
    </section>
@endsection
