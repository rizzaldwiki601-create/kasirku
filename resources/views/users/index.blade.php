@extends('layouts.app')
@section('title', 'Pengguna')
@section('heading', 'Pengguna')
@section('content')
    <div class="page-heading"><div><h1>Pengguna</h1><p>Kelola akun administrator dan kasir.</p></div><a class="button button-primary" href="{{ route('users.create') }}">＋ Tambah pengguna</a></div>
    <section class="panel">
        @if($users->isEmpty())<div class="empty-state"><strong>Belum ada pengguna</strong><p>Tambahkan akun pengguna untuk mulai menggunakan aplikasi.</p></div>@else
            <div class="table-wrap"><table><thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Bergabung</th><th class="align-right">Aksi</th></tr></thead><tbody>
                @foreach($users as $account)<tr><td><strong>{{ $account->name }}</strong>@if($account->is(auth()->user())) <span class="badge">Anda</span>@endif</td><td>{{ $account->email }}</td><td><span class="badge {{ $account->role === 'admin' ? 'badge-blue' : '' }}">{{ $account->role === 'admin' ? 'Admin' : 'Kasir' }}</span></td><td>{{ $account->created_at->format('d/m/Y') }}</td><td class="align-right actions"><a class="button button-secondary button-small" href="{{ route('users.edit', $account) }}">Edit</a><form method="POST" action="{{ route('users.destroy', $account) }}" onsubmit="return confirm('Hapus pengguna ini?')">@csrf @method('DELETE')<button class="button button-danger button-small" type="submit" @disabled($account->is(auth()->user()))>Hapus</button></form></td></tr>@endforeach
            </tbody></table></div><div class="pagination-wrap">{{ $users->links() }}</div>
        @endif
    </section>
@endsection
