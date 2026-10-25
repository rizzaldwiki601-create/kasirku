@extends('layouts.app')
@section('title', 'Detail produk')
@section('heading', 'Produk')
@section('content')
    <div class="page-heading"><div><h1>Detail produk</h1><p>Informasi lengkap produk {{ $product->name }}.</p></div><div class="heading-actions"><a class="button button-secondary" href="{{ route('products.index') }}">← Kembali</a><a class="button button-primary" href="{{ route('products.edit', $product) }}">Edit produk</a></div></div>
    <section class="panel detail-panel"><dl class="detail-grid"><div><dt>Nama produk</dt><dd>{{ $product->name }}</dd></div><div><dt>Kode produk / SKU</dt><dd>{{ $product->sku }}</dd></div><div><dt>Kategori</dt><dd>{{ $product->category->name }}</dd></div><div><dt>Harga beli</dt><dd>Rp {{ number_format((float) $product->purchase_price, 2, ',', '.') }}</dd></div><div><dt>Harga jual</dt><dd>Rp {{ number_format((float) $product->selling_price, 2, ',', '.') }}</dd></div><div><dt>Stok tersedia</dt><dd>{{ $product->stock }} {{ $product->unit }}</dd></div><div><dt>Stok minimum</dt><dd>{{ $product->minimum_stock }} {{ $product->unit }}</dd></div><div><dt>Diperbarui</dt><dd>{{ $product->updated_at->format('d/m/Y H:i') }}</dd></div></dl></section>
    <section class="panel"><div class="panel-heading"><div><h2>Riwayat perubahan stok</h2><p>20 perubahan terakhir, termasuk stok awal dan penjualan.</p></div></div>
        @if($stockMovements->isEmpty())<div class="empty-state"><strong>Belum ada perubahan stok</strong></div>@else
            <div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Jenis perubahan</th><th>Perubahan</th><th>Stok sebelum</th><th>Stok sesudah</th><th>Pengguna</th></tr></thead><tbody>@foreach($stockMovements as $movement)<tr><td>{{ $movement->created_at->format('d/m/Y H:i') }}</td><td>{{ ucfirst($movement->movement_type) }}</td><td>{{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}</td><td>{{ $movement->stock_before }}</td><td>{{ $movement->stock_after }}</td><td>{{ $movement->user?->name ?? 'Pengguna dihapus' }}</td></tr>@endforeach</tbody></table></div>
        @endif
    </section>
@endsection
