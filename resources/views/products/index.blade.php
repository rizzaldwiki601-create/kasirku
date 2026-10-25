@extends('layouts.app')
@section('title', 'Produk')
@section('heading', 'Produk')
@section('content')
    <div class="page-heading"><div><h1>Produk</h1><p>Kelola katalog, harga, dan ketersediaan stok.</p></div><a class="button button-primary" href="{{ route('products.create') }}">＋ Tambah produk</a></div>
    <section class="panel">
        <form class="toolbar" method="GET"><label class="search-field"><span>⌕</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama atau kode produk"></label><select name="category_id" aria-label="Filter kategori"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select><button class="button button-secondary" type="submit">Filter</button><a class="button button-quiet" href="{{ route('products.index') }}">Reset</a></form>
        @if($products->isEmpty())<div class="empty-state"><span class="empty-icon">▤</span><strong>Produk belum tersedia</strong><p>Tambahkan produk atau ubah kata pencarian.</p><a class="button button-primary" href="{{ route('products.create') }}">Tambah produk</a></div>
        @else
            <div class="table-wrap"><table><thead><tr><th>Produk</th><th>Kategori</th><th>Harga jual</th><th>Stok</th><th>Status</th><th class="align-right">Aksi</th></tr></thead><tbody>
                @foreach($products as $product)<tr><td><a class="text-link" href="{{ route('products.show', $product) }}"><strong>{{ $product->name }}</strong></a><small class="table-subtitle">{{ $product->sku }}</small></td><td>{{ $product->category->name }}</td><td>Rp {{ number_format((float) $product->selling_price, 2, ',', '.') }}</td><td>{{ $product->stock }} {{ $product->unit }}</td><td>@if($product->stock === 0)<span class="badge badge-red">Habis</span>@elseif($product->stock <= $product->minimum_stock)<span class="badge badge-orange">Menipis</span>@else<span class="badge badge-green">Tersedia</span>@endif</td><td class="align-right actions"><a class="button button-secondary button-small" href="{{ route('products.edit', $product) }}">Edit</a><form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Hapus produk ini?')">@csrf @method('DELETE')<button class="button button-danger button-small" type="submit">Hapus</button></form></td></tr>@endforeach
            </tbody></table></div><div class="pagination-wrap">{{ $products->links() }}</div>
        @endif
    </section>
@endsection
