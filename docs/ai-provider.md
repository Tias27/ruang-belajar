# Provider AI

Generasi AI dapat memakai 9router melalui API yang kompatibel dengan OpenAI Chat Completions.
Nama `GeminiService` dipertahankan agar controller dan job yang ada tetap memakai antarmuka yang sama.

Konfigurasi server di `.env`:

```dotenv
AI_PROVIDER=9router
AI_FALLBACK_PROVIDER=none
NINE_ROUTER_BASE_URL=http://202.134.242.51:20128/v1
NINE_ROUTER_API_KEY=
NINE_ROUTER_MODEL=ag/gemini-3.8-flash-medium
NINE_ROUTER_TIMEOUT=300
NINE_ROUTER_CONNECT_TIMEOUT=15
```

Isi API key hanya di `.env` server. Jangan masukkan ke frontend atau repository.
Endpoint HTTP mengirim kredensial dan materi tanpa enkripsi; gunakan HTTPS jika tersedia.

```powershell
php artisan config:clear
php artisan queue:restart
```

Restart queue diperlukan setelah mengganti konfigurasi jika worker berjalan terus-menerus.
Perubahan workspace lokal tidak otomatis diterapkan ke hosting. Terapkan kode dan konfigurasi
yang sama di hosting, lalu jalankan perintah tersebut di server.

Chat biasa/streaming, ringkasan, soal, kartu belajar, penilaian esai, dan konsolidasi memori
menggunakan model 9router yang sama. Gambar dikirim sebagai `image_url` data URL. Permintaan
terstruktur memakai `response_format: json_object`. Provider 9router tidak otomatis beralih
ke Gemini/Kimchi jika gagal. Error HTTP ditampilkan tanpa menyalin respons provider atau key.

## Embedding

Implementasi saat ini tidak memanggil API embedding dan tidak menyimpan vektor. Materi
disimpan sebagai teks, dipotong menjadi cuplikan, lalu dicari berdasarkan kata kunci dan
skor relevansi dalam `LearningSourceService` dan penyusunan konteks `GeminiService`.
Karena itu, model embedding tidak diperlukan untuk migrasi ini.

Jika pencarian semantik ditambahkan nanti, gunakan provider embedding terpisah atau model
lokal. Model untuk dokumen dan query harus sama; perubahan model embedding memerlukan
pembuatan ulang indeks vektor. Model chat tidak otomatis bisa dipakai sebagai model embedding.

## Validasi

```powershell
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --filter NineRouterTest
```

Tes memakai HTTP fake untuk routing provider, JSON, gambar, streaming, error, dan larangan
fallback otomatis. Uji koneksi langsung dengan prompt contoh telah berhasil untuk teks,
JSON, dan streaming pada 4 November 2026. Dokumen pengguna tidak dikirim dalam uji koneksi.
