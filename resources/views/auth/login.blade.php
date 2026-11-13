<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — SOSPEM</title>

    <script>
        (function() {
            const savedTheme = localStorage.getItem('sospem_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root,
        html[data-theme="dark"] {
            --color-canvas: #121212;
            --color-surface: #181818;
            --color-surface-subtle: #1f1f1f;
            --color-surface-pill: #2a2a2a;
            --color-border: #2b2b2b;
            --color-border-strong: #3d3d3d;
            --color-text-primary: #f5f5f5;
            --color-text-secondary: #9e9e9e;
            --color-text-muted: #6e6e6e;
            --color-teal: #18b26f;
            --color-teal-dark: #21ce83;
            --color-red-bg: rgba(240, 68, 56, 0.14);
            --color-red-border: rgba(240, 68, 56, 0.35);
            --color-red-dark: #f95950;
        }

        html[data-theme="light"] {
            --color-canvas: #f8fafc;
            --color-surface: #ffffff;
            --color-surface-subtle: #f8fafc;
            --color-surface-pill: #f1f5f9;
            --color-border: #e2e8f0;
            --color-border-strong: #cbd5e1;
            --color-text-primary: #0f172a;
            --color-text-secondary: #64748b;
            --color-text-muted: #94a3b8;
            --color-teal: #10b981;
            --color-teal-dark: #059669;
            --color-red-bg: #fff1f2;
            --color-red-border: #fecdd3;
            --color-red-dark: #be123c;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--color-canvas);
            color: var(--color-text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .login-card {
            width: 100%;
            max-width: 410px;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 10px;
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .login-logo {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: var(--color-teal);
            color: #ffffff;
            font-weight: 700;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .login-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--color-text-primary);
        }

        .login-sub {
            font-size: 12.5px;
            color: var(--color-text-secondary);
        }

        .login-error-box {
            background: var(--color-red-bg);
            border: 1px solid var(--color-red-border);
            color: var(--color-red-dark);
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 12.5px;
            line-height: 1.45;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-label {
            font-size: 12.5px;
            font-weight: 500;
            color: var(--color-text-secondary);
        }

        .form-input {
            width: 100%;
            padding: 10px 12px;
            font-size: 13.5px;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            background: var(--color-surface-subtle);
            color: var(--color-text-primary);
            outline: none;
        }
        .form-input:focus {
            border-color: var(--color-teal);
            background: var(--color-surface);
        }

        .password-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .toggle-pass-btn {
            position: absolute;
            right: 10px;
            background: transparent;
            border: none;
            color: var(--color-text-secondary);
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
        }
        .toggle-pass-btn:hover {
            color: var(--color-text-primary);
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12.5px;
            color: var(--color-text-secondary);
        }

        .remember-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
        }

        .btn-submit {
            width: 100%;
            padding: 10px 16px;
            border-radius: 6px;
            border: 1px solid var(--color-teal);
            background: var(--color-teal);
            color: #ffffff;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-submit:hover {
            background: #149a5f;
        }

        .demo-hint-box {
            border-top: 1px solid var(--color-border);
            padding-top: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-size: 12px;
            color: var(--color-text-secondary);
        }

        .btn-autofill {
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid var(--color-border);
            background: var(--color-surface-subtle);
            color: var(--color-text-primary);
            font-size: 11.5px;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-autofill:hover {
            border-color: var(--color-border-strong);
        }
    </style>
</head>
<body>
    <main class="login-card">
        <div class="login-brand">
            <div class="login-logo">S</div>
            <div>
                <h1 class="login-title">Masuk ke SOSPEM</h1>
                <p class="login-sub">Pemantauan Opini Publik &amp; Deteksi Jaringan Bot</p>
            </div>
        </div>

        @if($errors->any())
            <div class="login-error-box" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.submit') }}" method="POST" class="login-form">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}" placeholder="admin@sospem.id" required autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Kata Sandi</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" class="form-input" style="padding-right: 78px;" placeholder="Masukkan kata sandi" required>
                    <button type="button" class="toggle-pass-btn" onclick="togglePasswordVisibility(this)">Lihat</button>
                </div>
            </div>

            <div class="remember-row">
                <label class="remember-label">
                    <input type="checkbox" name="remember" value="1">
                    <span>Ingat sesi saya</span>
                </label>
            </div>

            <button type="submit" class="btn-submit">Masuk ke Dashboard</button>
        </form>

        <div class="demo-hint-box">
            <div>
                <div>Akun Default: <strong style="color: var(--color-text-primary);">admin@sospem.id</strong></div>
                <div>Sandi: <strong style="color: var(--color-text-primary);">password</strong></div>
            </div>
            <button type="button" class="btn-autofill" onclick="fillDefaultCredentials()">Isi Otomatis</button>
        </div>
    </main>

    <script>
        function togglePasswordVisibility(btn) {
            const input = document.getElementById('password');
            if (input.type === 'password') {
                input.type = 'text';
                btn.innerText = 'Tutup';
            } else {
                input.type = 'password';
                btn.innerText = 'Lihat';
            }
        }

        function fillDefaultCredentials() {
            document.getElementById('email').value = 'admin@sospem.id';
            document.getElementById('password').value = 'password';
        }
    </script>
</body>
</html>

