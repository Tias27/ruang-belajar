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

Ganti password admin sebelum dipakai di server.


Nginx harus diarahkan ke folder:

```text
/var/www/ruangbelajar/public
```
