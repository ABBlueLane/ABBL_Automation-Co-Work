<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>Login | ABBL Automation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('images/icon.ico') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.min.css') }}">
    <style>
        body {
            min-height: 100vh;
            background: #f3f6f9;
        }

        .auth-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .auth-panel {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border: 1px solid var(--vz-border-color);
            border-radius: 8px;
            box-shadow: var(--vz-box-shadow-sm);
            padding: 28px;
        }

        .auth-logo {
            display: block;
            height: 34px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <main class="auth-shell">
        <section class="auth-panel">
            <img class="auth-logo" src="{{ asset('images/black-logo-bluelane.webp') }}" alt="ABBL Automation">
            <h1 class="h4 mb-2">เข้าสู่ระบบ</h1>
            <p class="text-muted mb-4">ใช้ Email หรือ Phone และรหัสผ่านของผู้ดูแลระบบ</p>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->has('login'))
                <div class="alert alert-danger">{{ $errors->first('login') }}</div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf
                <input type="hidden" name="red" value="{{ old('red', $redirectTo) }}">

                <div class="mb-3">
                    <label for="login" class="form-label">Email หรือ Phone</label>
                    <input type="text" class="form-control @error('login') is-invalid @enderror" id="login" name="login" value="{{ old('login') }}" required autofocus>
                    @error('login')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </section>
    </main>
</body>
</html>
