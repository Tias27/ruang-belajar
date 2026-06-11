<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Terjadi Kendala' }} - RuangBelajar AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        campus: {50:'#f3f9ff',100:'#e1f1ff',700:'#1456a3',900:'#0b1f44'},
                        accent: {50:'#f5f2ff',700:'#5d3ed8'}
                    }
                }
            }
        };
    </script>
</head>
<body class="min-h-screen bg-[#f8fbff] text-slate-900 antialiased">
    <main class="mx-auto flex min-h-screen max-w-3xl items-center px-5 py-10">
        <section class="w-full rounded-lg border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="inline-flex items-center gap-2 rounded-lg bg-campus-50 px-3 py-2 text-sm font-semibold text-campus-700">
                <span>RuangBelajar AI</span>
            </div>
            <p class="mt-6 text-sm font-semibold text-campus-700">{{ $code ?? 'Error' }}</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-campus-900 sm:text-3xl">{{ $title ?? 'Terjadi kendala' }}</h1>
            <p class="mt-3 text-sm leading-6 text-slate-500">{{ $message ?? 'Halaman belum bisa dibuka saat ini.' }}</p>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <a href="{{ url('/') }}" class="inline-flex justify-center rounded-lg bg-campus-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                    Kembali ke Beranda
                </a>
                <button type="button" onclick="history.back()" class="inline-flex justify-center rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Kembali
                </button>
            </div>
        </section>
    </main>
</body>
</html>
