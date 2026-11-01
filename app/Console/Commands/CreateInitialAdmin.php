<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateInitialAdmin extends Command
{
    protected $signature = 'kasir:buat-admin';

    protected $description = 'Membuat akun admin pertama secara interaktif';

    public function handle(): int
    {
        if (! $this->laravel->environment('production')) {
            $this->error('Perintah ini hanya boleh dijalankan di environment production.');

            return self::FAILURE;
        }

        if (config('database.default') !== 'mysql' || DB::connection()->getDriverName() !== 'mysql') {
            $this->error('Perintah ini hanya boleh dijalankan dengan koneksi database MySQL.');

            return self::FAILURE;
        }

        $email = $this->ask('Email admin');

        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email admin wajib berupa alamat email yang valid.');

            return self::FAILURE;
        }

        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            if ($existingUser->role !== 'admin') {
                $this->error('Email tersebut sudah digunakan oleh akun non-admin. Akun tidak diubah.');

                return self::FAILURE;
            }

            $password = $this->secret('Kata sandi admin untuk verifikasi');

            if (! is_string($password) || ! Hash::check($password, $existingUser->password)) {
                $this->error('Akun admin sudah ada, tetapi kata sandinya tidak cocok. Akun tidak diubah.');

                return self::FAILURE;
            }

            $this->info('Akun admin sudah tersedia dan kata sandinya cocok. Tidak ada perubahan.');

            return self::SUCCESS;
        }

        $name = $this->ask('Nama admin');
        $password = $this->secret('Kata sandi (minimal 8 karakter)');

        if (! is_string($name) || trim($name) === '' || strlen((string) $password) < 8) {
            $this->error('Nama wajib diisi dan kata sandi harus memiliki minimal 8 karakter.');

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
