# Respons AI

Sapaan tunggal dan pertanyaan tanggal/jam sederhana dijawab oleh QuickChatReply
sebelum persiapan konteks dan permintaan AI. Waktu dihitung setiap pesan dalam
Asia/Jakarta (WIB), bukan cache jawaban. Pencocokan seluruh pesan mencegah
pertanyaan tambahan atau zona waktu lain ikut terpotong. Gambar dan lampiran
materi tetap melalui AI. Jawaban langsung mengikuti penyimpanan riwayat dan
format streaming yang sama; ini tidak mempercepat waktu respons model untuk
pertanyaan lain.

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

Model, batas konteks, jumlah soal, serta proses sinkron soal dan flashcard tetap
dipertahankan. Chat menampilkan hasil secara bertahap.
Ringkasan menerima streaming di server, lalu menampilkan hasil lengkap setelah
validasi dan penyimpanan. File baru disiapkan otomatis setelah upload, melalui
worker terpisah; lihat [automatic-summaries.md](automatic-summaries.md).

Ringkasan memakai Markdown berbagian, bukan JSON panjang. Prompt menargetkan
45-48% jumlah kata konteks sumber untuk total isi, poin penting, dan kesimpulan.
Teks sumber tidak diubah; metadata raw_response._length mencatat target dan
panjang aktual. Persentase merupakan target penulisan, bukan pemotongan teks
atau jaminan tepat. Materi sangat pendek/berulang dan batas output 8192 token
bisa membuat hasil berbeda; tidak ada request kedua otomatis untuk mengejar rasio.
Uji langsung teks sintetis 576 kata menghasilkan 258 kata (44,79%) setelah
penyesuaian anggaran tiap bagian. Ini satu sampel, bukan jaminan rasio setiap materi.
Penanganan batas waktu, hasil kosong/terpotong, dan hasil pengukuran dijelaskan
di [summary-latency.md](summary-latency.md).
