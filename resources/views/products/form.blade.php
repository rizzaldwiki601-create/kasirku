@extends('layouts.app')
@section('title', $product->exists ? 'Edit produk' : 'Tambah produk')
@section('heading', 'Produk')
@section('content')
    <div class="page-heading"><div><h1>{{ $product->exists ? 'Edit produk' : 'Tambah produk' }}</h1><p>Data harga disimpan dalam Rupiah dan stok dalam satuan barang.</p></div><a class="button button-secondary" href="{{ route('products.index') }}">← Kembali</a></div>
    <section class="panel form-panel"><form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="form-stack">
        @csrf @if($product->exists) @method('PUT') @endif
        <div class="form-grid">
            <label>Kategori <span class="required">*</span><select name="category_id" required><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select>@error('category_id')<span class="field-error">{{ $message }}</span>@enderror</label>
            <label>Kode produk / SKU <span class="required">*</span><input name="sku" value="{{ old('sku', $product->sku) }}" maxlength="100" required>@error('sku')<span class="field-error">{{ $message }}</span>@enderror</label>
            <label>Nama produk <span class="required">*</span><input name="name" value="{{ old('name', $product->name) }}" maxlength="255" required>@error('name')<span class="field-error">{{ $message }}</span>@enderror</label>
            <label>Satuan <span class="required">*</span><input name="unit" value="{{ old('unit', $product->unit ?: 'pcs') }}" maxlength="30" required>@error('unit')<span class="field-error">{{ $message }}</span>@enderror</label>
            <label>Harga beli (Rp) <span class="required">*</span><input type="number" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" min="0" step="0.01" required>@error('purchase_price')<span class="field-error">{{ $message }}</span>@enderror</label>
            <label>Harga jual (Rp) <span class="required">*</span><input type="number" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" min="0" step="0.01" required>@error('selling_price')<span class="field-error">{{ $message }}</span>@enderror</label>
            <label>Stok <span class="required">*</span><input type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" min="0" step="1" required>@error('stock')<span class="field-error">{{ $message }}</span>@enderror</label>
            <label>Ambang stok minimum <span class="required">*</span><input type="number" name="minimum_stock" value="{{ old('minimum_stock', $product->minimum_stock ?? 0) }}" min="0" step="1" required>@error('minimum_stock')<span class="field-error">{{ $message }}</span>@enderror</label>
        </div>
        <div class="form-actions"><a class="button button-secondary" href="{{ route('products.index') }}">Batal</a><button class="button button-primary" type="submit">Simpan produk</button></div>
    </form></section>
@endsection
