<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Terjadi Kendala' }} - Ruang Belajar</title>
    <link rel="stylesheet" href="{{ asset('ui/prototype.css') }}">
    <link rel="stylesheet" href="{{ asset('ui/friendly.css') }}">
    <style>
        body { min-height: 100dvh; display: grid; place-items: center; background: #faf9f4; padding: 24px; }
        main { width: min(100%, 620px); padding: 0; }
        section { background: #fffefb; border: 1px solid #dfe5db; border-radius: 24px; padding: clamp(24px, 5vw, 48px); }
        .error-brand { font-size: 20px; letter-spacing: -1px; font-weight: 700; }
        .error-code { font-size: 12px; color: #686d66; margin-top: 42px; }
        h1 { font-size: calc(clamp(26px, 5vw, 36px) - 1px); font-weight: 600; line-height: 1.3; }
        .error-message { font-size: 13px; color: #50564e; line-height: 1.8; margin-top: 18px; }
        .error-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 28px; }
        .button { border-radius: 24px; }
        .button-primary { background: #416f94; }
        .button-primary:hover { background: #315a7c; }
    </style>
</head>
<body>
    <main>
        <section>
            <a class="error-brand" href="{{ url('/') }}">ruangbelajar</a>
            <p class="error-code">{{ $code ?? 'Error' }}</p>
            <h1>{{ $title ?? 'Ada kendala sebentar' }}</h1>
            <p class="error-message">{{ $message ?? 'Halaman belum bisa dibuka saat ini.' }}</p>
            <div class="error-actions">
                <a href="{{ url('/') }}" class="button button-primary">Kembali ke beranda</a>
                <button type="button" onclick="history.back()" class="button button-secondary">Kembali</button>
            </div>
        </section>
    </main>
</body>
</html>
