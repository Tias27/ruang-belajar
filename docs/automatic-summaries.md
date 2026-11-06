# Ringkasan otomatis setelah upload

Upload mengekstrak dan menyimpan teks terlebih dahulu. File berstatus processed
dan berisi teks kemudian mendapat reservasi ringkasan queued dan satu job pada
connection database ai_summaries, queue ai-summaries. Request upload tidak
menjalankan AI atau menunggu ringkasan. Berlaku untuk upload biasa, upload folder,
tambahan file folder, lampiran chat, dan materi baru ruang belajar.

Ini hanya menyiapkan ringkasan masing-masing file baru. Ringkasan gabungan folder
dan subset tetap dibuat saat diminta. File lama tidak diproses massal. Model,
batas materi, dan kedalaman ringkasan mengikuti konfigurasi yang sama.

Teks ekstraksi tetap menjadi sumber asli untuk semua fitur. Job ringkasan tidak
mengubah extracted_text, status dokumen, atau catatan pembacaan. DOCX/PPTX
memerlukan ekstensi PHP ZIP; periksa php.ini pada proses web yang benar, karena
PHP CLI dan Laragon dapat memakai konfigurasi berbeda. Setelah mengaktifkan
ekstensi, restart proses PHP yang melayani upload.

## Menjalankan worker

Lokal atau hosting:

```sh
php artisan ai:summary-worker
```

Windows juga bisa memakai `scripts/summary-worker.ps1`. Di Laragon maupun saat
memakai `composer run dev`, worker perlu tetap berjalan di proses terpisah.
Setelah restart komputer, jalankan kembali worker.
Hosting: jalankan melalui Supervisor/systemd dengan restart otomatis.

Command memakai file lock untuk membatasi satu worker per host. Queue terpisah
dari memori chat dan job default. Job timeout 360 detik, retry_after 420 detik,
satu percobaan; gagal ditandai agar user dapat mencoba lagi. Batas eksekusi PHP
dikembalikan setelah job agar worker tidak mati saat idle di Windows.

Setelah deploy: `php artisan config:clear`, `php artisan route:clear`,
`npm run build`, dan restart worker agar memuat kode baru. Tidak ada migrasi baru;
kolom status string yang ada mendukung queued.

## Saat Ringkas diklik

- Completed yang sumbernya cocok langsung dibuka.
- Queued dapat dikerjakan langsung oleh request user, memakai reservasi sama.
  Ini juga membuat Ringkas tetap berfungsi jika worker belum dijalankan.
- Processing dipantau melalui endpoint status milik user. Halaman materi tetap
  terbuka sampai hasil selesai, tanpa reload berulang atau permintaan AI kedua.
- Failed menampilkan Coba lagi; retry memakai aturan reuse yang sudah ada.

Worker dan request user mengambil hak menjalankan ringkasan dalam transaksi
singkat dengan row lock. Hanya satu eksekusi dapat mengklaim baris; job yang tiba
setelah hasil selesai dilewati. Lama menunggu antrean tidak menyebabkan duplikat.
Processing terhenti lebih dari enam menit menjadi failed saat status diperiksa.
Dokumen dihapus atau isi berubah sebelum job berjalan tidak dikirim dari job lama.
Teks/galat internal provider tidak dikirim lewat endpoint status.

Tes AutomaticSummaryTest memakai SQLite in-memory, mock AI, direktori upload
terisolasi, termasuk konsumsi job lewat database queue. Browser --summary-wait
menguji halaman tetap diam, klik ganda, hasil selesai, gangguan polling, dan retry
pada mobile/desktop. Pengujian tidak membuat ringkasan dari dokumen pengguna.
