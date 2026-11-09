<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Admin — LARIS</title>
    <style>
        :root { --primary:#1B4D3E; --accent:#FCD804; --background:#FAF9F6; --surface:#FFFFFF; --border:#E5E9E5; --text:#202522; --muted:#69736D; --danger:#A33232; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:24px; background:var(--background); color:var(--text); font-family:system-ui,-apple-system,"Segoe UI",sans-serif; }
        main { width:min(100%,420px); background:var(--surface); border:1px solid var(--border); border-radius:8px; padding:32px; }
        .brand { display:flex; align-items:center; gap:12px; margin-bottom:28px; }
        .mark { display:grid; place-items:center; width:40px; height:40px; border-radius:6px; background:var(--primary); color:white; font-weight:800; }
        .brand-name { font-size:18px; font-weight:750; letter-spacing:.02em; }
        .brand-caption { color:var(--muted); font-size:12px; }
        h1 { margin:0 0 6px; font-size:24px; line-height:1.2; }
        p { margin:0 0 24px; color:var(--muted); font-size:14px; }
        label { display:block; margin:16px 0 6px; font-size:13px; font-weight:650; }
        input { width:100%; min-height:44px; padding:10px 12px; border:1px solid var(--border); border-radius:6px; font:inherit; background:var(--surface); }
        input:focus-visible, button:focus-visible { outline:2px solid var(--accent); outline-offset:2px; }
        button { width:100%; min-height:44px; margin-top:24px; border:1px solid var(--primary); border-radius:6px; background:var(--primary); color:white; font:inherit; font-weight:700; cursor:pointer; }
        .error { padding:10px 12px; margin:0 0 16px; border:1px solid var(--danger); border-radius:6px; color:var(--danger); font-size:13px; }
        @media (max-width:480px) { main { padding:24px; } }
    </style>
</head>
<body>
    <main>
        <div class="brand">
            <div class="mark" aria-hidden="true">L</div>
            <div><div class="brand-name">LARIS</div><div class="brand-caption">Konsol Administrator</div></div>
        </div>
        <h1>Masuk admin</h1>
        <p>Gunakan akun administrator untuk mengelola lisensi, paket, dan perangkat.</p>
        @if ($errors->any())
            <div class="error" role="alert">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('admin.login') }}">
            @csrf
            <label for="email">Email admin</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            <label for="password">Kata sandi</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="submit">Masuk ke dashboard</button>
        </form>
    </main>
</body>
</html>
