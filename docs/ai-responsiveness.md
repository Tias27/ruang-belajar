# Respons AI

Chat tetap memakai streaming 9router. Pembaca SSE meneruskan setiap baris tanpa
menunggu blok 1024 byte. Respons mengirim header `X-Accel-Buffering: no` dan
`Cache-Control: no-cache, no-transform`. Reverse proxy/CDN juga harus menonaktifkan
buffering dan kompresi untuk respons `text/event-stream`.

Memori chat masuk antrean database terpisah `ai_memory`, queue `ai-memory`.
Jalankan **satu worker** agar urutan percakapan tetap FIFO:

```sh
php artisan queue:work ai_memory --queue=ai-memory --sleep=2 --timeout=600 --tries=0
```

Windows: `powershell -ExecutionPolicy Bypass -File scripts/ai-memory-worker.ps1`.
Hosting: jalankan perintah PHP tersebut melalui Supervisor/systemd, satu proses,
autorestart aktif. Queue worker default tidak mengambil antrean ini.
Tanpa worker, chat tetap berjalan, tetapi memori baru menunggu di antrean.
Setelah deploy, jalankan `php artisan config:clear` dan `php artisan queue:restart`.

Lock per pengguna dan materi mencegah dua konsolidasi menimpa memori bersamaan.
Timeout job 600 detik, lock 650 detik, retry_after 660 detik. Job kadaluarsa
setelah satu hari. Jika materi/pengguna terhapus, job dibuang. Kegagalan memasukkan
memori ke antrean dicatat tanpa menggagalkan jawaban yang sudah selesai.

Teks ekstraksi sudah disimpan di database. Cache tambahan menyimpan hasil
pemotongan konteks, pemisahan baris, dan potongan sumber selama enam jam pada
cache Laravel. Kunci berdasarkan hash isi dan parameter persiapan; perubahan isi
atau batas konteks otomatis memakai entri baru. Cache tidak menyimpan jawaban AI,
riwayat chat, atau memori pengguna. Cache gagal tetap menjalankan persiapan biasa.

Model, prompt, batas konteks/output, jumlah soal, serta proses sinkron ringkasan,
soal, dan flashcard tidak berubah. Streaming mengurangi waktu menunggu tampilan;
kecepatan menghasilkan jawaban masih bergantung pada provider.
