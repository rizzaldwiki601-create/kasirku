<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateInitialAdmin extends Command
{
    protected $signature = 'kasir:buat-admin';

    protected $description = 'Membuat akun admin pertama secara interaktif';

    public function handle(): int
    {
        if (User::where('role', 'admin')->exists()) {
            $this->error('Akun admin sudah tersedia. Gunakan menu Pengguna untuk mengelola akun.');

            return self::FAILURE;
        }

        $name = $this->ask('Nama admin');
        $email = $this->ask('Email admin');
        $password = $this->secret('Kata sandi (minimal 8 karakter)');

        if (! is_string($name) || trim($name) === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen((string) $password) < 8) {
            $this->error('Nama, email valid, dan kata sandi minimal 8 karakter wajib diisi.');

            return self::FAILURE;
        }
        if (User::where('email', $email)->exists()) {
            $this->error('Email tersebut sudah digunakan.');

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);

        $this->info('Akun admin berhasil dibuat. Simpan kata sandi di tempat yang aman.');

        return self::SUCCESS;
    }
}
