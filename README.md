# RuangBelajar AI

RuangBelajar AI adalah aplikasi web Laravel untuk belajar dari dokumen. Pengguna dapat mengunggah PDF, DOCX, dan PPTX, lalu memakai AI untuk ringkasan, tanya jawab materi, latihan soal, dan flashcard.

## Stack

- Laravel 12
- PHP 8.3+
- MySQL atau MariaDB
- Blade, Tailwind CSS, Alpine.js
- AI provider: Kimchi/OpenAI compatible atau Gemini

## Fitur Utama

- Login dan registrasi pengguna
- Upload banyak file sekaligus
- Folder gabungan untuk belajar dari beberapa file sebagai satu paket
- Ringkasan AI
- Tanya AI berdasarkan isi dokumen/folder
- Latihan soal pilihan ganda
- Flashcard
- Riwayat chat AI
- Panel admin untuk pengguna dan dokumen

## Setup Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Login admin default setelah seed:

```text
username: admin
password: password
```

Ganti password admin sebelum dipakai di server.

## Deploy VPS

Panduan lengkap ada di [DEPLOY_VPS.md](DEPLOY_VPS.md).

Ringkasnya:

```bash
git clone https://github.com/USERNAME/NAMA_REPO.git /var/www/ruangbelajar
cd /var/www/ruangbelajar
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Nginx harus diarahkan ke folder:

```text
/var/www/ruangbelajar/public
```

## Catatan Keamanan

- Jangan upload `.env` ke GitHub.
- Jangan upload `vendor`, `node_modules`, `storage/logs`, dan dokumen private.
- Gunakan `APP_DEBUG=false` di server.
- Rotate API key dan password database sebelum production.
