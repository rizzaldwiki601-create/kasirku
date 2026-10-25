@extends('layouts.app')
@section('title', $category->exists ? 'Edit kategori' : 'Tambah kategori')
@section('heading', 'Kategori')
@section('content')
    <div class="page-heading"><div><h1>{{ $category->exists ? 'Edit kategori' : 'Tambah kategori' }}</h1><p>Lengkapi informasi kategori produk.</p></div><a class="button button-secondary" href="{{ route('categories.index') }}">← Kembali</a></div>
    <section class="panel form-panel"><form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" class="form-stack">
        @csrf @if($category->exists) @method('PUT') @endif
        <label>Nama kategori <span class="required">*</span><input type="text" name="name" value="{{ old('name', $category->name) }}" maxlength="255" required>@error('name')<span class="field-error">{{ $message }}</span>@enderror</label>
        <label>Deskripsi<textarea name="description" rows="4" maxlength="2000">{{ old('description', $category->description) }}</textarea>@error('description')<span class="field-error">{{ $message }}</span>@enderror</label>
        <div class="form-actions"><a class="button button-secondary" href="{{ route('categories.index') }}">Batal</a><button class="button button-primary" type="submit">Simpan kategori</button></div>
    </form></section>
@endsection
