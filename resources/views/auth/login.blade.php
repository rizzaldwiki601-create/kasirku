<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#183c32">
    <title>Masuk | KASIRKU</title>
    <link rel="stylesheet" href="{{ asset('css/kasirku.css') }}">
</head>
<body class="login-page">
    <main class="login-layout">
        <section class="login-story" aria-label="Tentang KASIRKU">
            <a class="brand login-brand" href="{{ route('login') }}"><span class="brand-mark" aria-hidden="true">K</span><span>KASIRKU<small>Retail operations</small></span></a>
            <div class="login-story-copy">
                <span class="eyebrow"><span></span> SISTEM OPERASIONAL TOKO</span>
                <h1>Setiap transaksi,<br><em>lebih teratur.</em></h1>
                <p>Kelola penjualan, persediaan, dan laporan toko dalam satu ruang kerja yang jelas.</p>
            </div>
            <div class="login-story-foot"><span>01</span><span>Penjualan · Persediaan · Laporan</span></div>
            <div class="login-art" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
        </section>
        <section class="login-form-side">
            <div class="login-card">
                <div class="login-intro"><span class="eyebrow login-eyebrow">SELAMAT DATANG KEMBALI</span><h2>Masuk ke akun Anda</h2><p>Gunakan email dan kata sandi untuk melanjutkan.</p></div>
                @if ($errors->any())<div class="alert alert-error" role="alert">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('login.store') }}" class="form-stack">
                    @csrf
                    <label>Email
                        <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="nama@toko.com" required autofocus>
                        @error('email')<span class="field-error">{{ $message }}</span>@enderror
                    </label>
                    <label>Kata sandi
                        <span class="password-field"><input id="login-password" type="password" name="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required><button class="password-toggle" type="button" aria-controls="login-password" aria-label="Tampilkan kata sandi" aria-pressed="false" data-password-toggle>Tampilkan</button></span>
                        @error('password')<span class="field-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="checkbox-label"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                    <button class="button button-primary button-full" type="submit">Masuk <span aria-hidden="true">→</span></button>
                </form>

                @php
                    $demoEmail = config('demo.login_email');
                    $demoPassword = config('demo.login_password');
                @endphp
                @if(config('demo.login_enabled') && filled($demoEmail) && filled($demoPassword))
                    <div class="demo-panel" aria-live="polite">
                        <div class="demo-panel-header">
                            <span class="eyebrow demo-eyebrow">AKSES DEMO KASIRKU</span>
                            <span class="demo-copy-actions">
                                <button class="button button-secondary button-small" type="button" data-copy-demo="demo-email">Salin Email</button>
                                <button class="button button-secondary button-small" type="button" data-copy-demo="demo-password">Salin Password</button>
                            </span>
                        </div>
                        <p>Gunakan akun demo untuk mencoba fitur tanpa mengubah data utama.</p>
                        <div class="demo-credentials">
                            <label>Email
                                <input id="demo-email" type="text" value="{{ $demoEmail }}" readonly data-demo-email>
                            </label>
                            <label>Password
                                <input id="demo-password" type="text" value="{{ $demoPassword }}" readonly data-demo-password>
                            </label>
                        </div>
                        <form method="POST" action="{{ route('login.store') }}" class="demo-login-form">
                            @csrf
                            <input type="hidden" name="email" value="{{ $demoEmail }}">
                            <input type="hidden" name="password" value="{{ $demoPassword }}">
                            <button class="button button-primary button-full" type="submit">Masuk sebagai Demo</button>
                        </form>
                    </div>
                @endif

                <p class="login-note">Akun dikelola oleh administrator. Registrasi publik tidak tersedia.</p>
            </div>
            <span class="login-copyright">KASIRKU <span>·</span> Sistem penjualan dan persediaan</span>
        </section>
    </main>
    <script>
        const passwordInput = document.getElementById('login-password');
        const passwordToggle = document.querySelector('[data-password-toggle]');
        passwordToggle?.addEventListener('click', () => {
            const visible = passwordInput.type === 'password';
            passwordInput.type = visible ? 'text' : 'password';
            passwordToggle.textContent = visible ? 'Sembunyikan' : 'Tampilkan';
            passwordToggle.setAttribute('aria-label', visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            passwordToggle.setAttribute('aria-pressed', String(visible));
        });

        document.querySelectorAll('[data-copy-demo]').forEach((copyButton) => {
            copyButton.addEventListener('click', async () => {
                const input = document.getElementById(copyButton.dataset.copyDemo);
                if (!input) return;
                try {
                    await navigator.clipboard.writeText(input.value);
                    copyButton.textContent = 'Disalin';
                } catch (error) {
                    copyButton.textContent = 'Salin gagal';
                }
            });
        });
    </script>
</body>
</html>
