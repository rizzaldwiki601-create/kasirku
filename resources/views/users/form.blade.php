@extends('layouts.app')
@section('title', $account->exists ? 'Edit pengguna' : 'Tambah pengguna')
@section('heading', 'Pengguna')
@section('content')
    <div class="page-heading"><div><h1>{{ $account->exists ? 'Edit pengguna' : 'Tambah pengguna' }}</h1><p>Hanya admin yang dapat mengelola akun dan peran pengguna.</p></div><a class="button button-secondary" href="{{ route('users.index') }}">← Kembali</a></div>
    <section class="panel form-panel"><form method="POST" action="{{ $account->exists ? route('users.update', $account) : route('users.store') }}" class="form-stack">
        @csrf @if($account->exists) @method('PUT') @endif
        <label>Nama lengkap <span class="required">*</span><input name="name" value="{{ old('name', $account->name) }}" required maxlength="255">@error('name')<span class="field-error">{{ $message }}</span>@enderror</label>
        <label>Email <span class="required">*</span><input type="email" name="email" value="{{ old('email', $account->email) }}" required maxlength="255">@error('email')<span class="field-error">{{ $message }}</span>@enderror</label>
        <label>Peran <span class="required">*</span><select name="role" required><option value="kasir" @selected(old('role', $account->role ?: 'kasir') === 'kasir')>Kasir</option><option value="admin" @selected(old('role', $account->role) === 'admin')>Admin</option><option value="demo" @selected(old('role', $account->role) === 'demo')>Demo</option></select>@error('role')<span class="field-error">{{ $message }}</span>@enderror</label>
        <label>Kata sandi {{ $account->exists ? '(kosongkan jika tidak diubah)' : '*' }}<input type="password" name="password" @required(!$account->exists) minlength="8" autocomplete="new-password">@error('password')<span class="field-error">{{ $message }}</span>@enderror</label>
        <label>Konfirmasi kata sandi<input type="password" name="password_confirmation" @required(!$account->exists) minlength="8" autocomplete="new-password"></label>
        <div class="form-actions"><a class="button button-secondary" href="{{ route('users.index') }}">Batal</a><button class="button button-primary" type="submit">Simpan pengguna</button></div>
    </form></section>
@endsection
