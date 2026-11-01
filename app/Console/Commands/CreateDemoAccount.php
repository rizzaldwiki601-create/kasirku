<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateDemoAccount extends Command
{
    protected $signature = 'kasir:buat-demo';

    protected $description = 'Membuat akun demo dari environment tanpa mengubah akun yang sudah ada';

    public function handle(): int
    {
        if (! config('demo.login_enabled')) {
            $this->error('DEMO_LOGIN_ENABLED harus bernilai true sebelum akun demo dibuat.');

            return self::FAILURE;
        }

        $email = config('demo.login_email');
        $password = config('demo.login_password');

        if ($email !== 'demo@kasirku.test' || ! is_string($password) || $password === '') {
            $this->error('DEMO_LOGIN_EMAIL harus memakai email demo yang ditetapkan dan DEMO_LOGIN_PASSWORD wajib diatur.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            if ($user->role !== 'demo') {
                $this->error('Email demo sudah digunakan oleh akun non-demo. Akun tersebut tidak diubah.');

                return self::FAILURE;
            }

            if (! Hash::check($password, $user->password)) {
                $this->error('Akun demo sudah ada, tetapi kata sandinya berbeda dari environment. Akun tidak diubah.');

                return self::FAILURE;
            }

            $this->info('Akun demo sudah tersedia dan kredensialnya sesuai.');

            return self::SUCCESS;
        }

        User::create([
            'name' => 'Demo KASIRKU',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'demo',
        ]);

        $this->info('Akun demo berhasil dibuat.');

        return self::SUCCESS;
    }
}
