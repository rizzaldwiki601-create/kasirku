@extends('layouts.app')
@section('title', 'Kategori')
@section('heading', 'Kategori')
@section('content')
    <div class="page-heading"><div><h1>Kategori produk</h1><p>Kelompokkan produk agar lebih mudah dikelola.</p></div><a class="button button-primary" href="{{ route('categories.create') }}">＋ Tambah kategori</a></div>
    <section class="panel">
        <form class="toolbar" method="GET"><label class="search-field"><span>⌕</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama kategori"></label><button class="button button-secondary" type="submit">Cari</button>@if(request('search'))<a class="button button-quiet" href="{{ route('categories.index') }}">Reset</a>@endif</form>
        @if($categories->isEmpty())<div class="empty-state"><span class="empty-icon">▦</span><strong>Belum ada kategori</strong><p>Tambahkan kategori untuk mulai mengelompokkan produk.</p><a class="button button-primary" href="{{ route('categories.create') }}">Tambah kategori</a></div>
        @else
            <div class="table-wrap"><table><thead><tr><th>Nama kategori</th><th>Deskripsi</th><th>Jumlah produk</th><th class="align-right">Aksi</th></tr></thead><tbody>
                @foreach($categories as $category)<tr><td><strong>{{ $category->name }}</strong></td><td>{{ $category->description ?: '—' }}</td><td>{{ $category->products_count }}</td><td class="align-right actions"><a class="button button-secondary button-small" href="{{ route('categories.edit', $category) }}">Edit</a><form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini?')">@csrf @method('DELETE')<button class="button button-danger button-small" type="submit">Hapus</button></form></td></tr>@endforeach
            </tbody></table></div><div class="pagination-wrap">{{ $categories->links() }}</div>
        @endif
    </section>
@endsection
