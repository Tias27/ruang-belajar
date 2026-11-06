# UI aplikasi Ruang Belajar

Desain yang disetujui di `docs/ui-concept/` sudah diterapkan ke halaman Laravel.
Preview HTML tetap tersedia sebagai referensi; aplikasi memakai route, form, dan data backend.

## Membuka aplikasi

Jalankan dari folder proyek:

```powershell
php artisan serve
```

Buka `http://127.0.0.1:8000`. Masuk memakai akun aplikasi yang sudah ada.
Aset produksi sudah dibangun di `public/build`, jadi Vite tidak perlu menyala untuk melihat UI.

Jika mengubah Blade, JavaScript, atau Tailwind:

```powershell
npm.cmd install
npm.cmd run build
php artisan view:clear
```

## Bagian yang diterapkan

- Halaman awal, masuk, daftar, lupa kata sandi, dan reset kata sandi.
- Navigasi desktop dan HP, beranda dengan data akun, panduan, profil, dan tema pengelola.
- Materi, folder, upload, ringkasan, Tanya AI, latihan soal, kartu belajar, catatan, dan riwayat.
- Belajar bareng: ilustrasi, gabung PIN, buat ruang dari materi, upload, dan chat.
- Ilustrasi SVG bergerak, tombol jeda berupa ikon, serta dukungan reduced motion.

CSS konsep dan font lokal ada di `public/ui/`. Berkas `*-live.css` menyesuaikan kebutuhan halaman nyata.
CSS konsep memakai layer `components` supaya utilitas Tailwind tetap bekerja pada form dan dialog.
Alpine, Lucide, Echo, dan Pusher dibangun lewat Vite. Grafik admin memakai Chart.js lokal.
`resources/js/icons.js` hanya memuat ikon yang dipakai aplikasi; tambahkan impor saat memakai ikon baru.

## Validasi

```powershell
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit
npm.cmd run build
php artisan view:cache
```

Jika ekstensi SQLite sudah aktif di `php.ini`, kedua opsi `-d` boleh dihapus.
Tes memakai SQLite dalam memori, termasuk login/daftar, akses halaman, kepemilikan data,
review kartu, simpan catatan, jawaban kuis, dan pilihan file folder.
Panggilan provider AI tidak dijalankan pada pengujian UI.

`VIEW_COMPILED_PATH` di konfigurasi lokal dikosongkan agar cache Blade memakai
`storage/framework/views` proyek ini, bukan path proyek lama.

## Daftar dan pagination

- Rak materimu: 2 materi per halaman; materi terbaru di hero tetap sama saat berpindah halaman.
- Aktivitas beranda: 4 per halaman.
- Riwayat percakapan, hasil belajar, daftar file/folder, dan Belajar bareng: 5 per halaman.
- Setiap daftar memakai parameter halaman sendiri agar pencarian dan posisi daftar lain tetap terbawa.
- Pagination memakai label Sebelumnya/Berikutnya. Baris daftar dan kartu pengingat dipadatkan di HP.
- Klik pagination mengganti daftar lewat `resources/js/pagination.js`, tanpa reload halaman penuh.
  Posisi scroll, catatan yang belum disimpan, pencarian, serta halaman daftar lain tetap terjaga.
  Back/Forward browser didukung; kegagalan koneksi menampilkan tombol Coba lagi.
- Link biasa tetap tersedia jika JavaScript tidak aktif atau dibuka di tab baru.
- Saat berpindah ke halaman dengan item lebih sedikit, ruang sisa diletakkan sebelum
  kontrol pagination supaya tombol tidak naik. Pengaturan tinggi direset saat lebar
  viewport berubah agar mengikuti layout responsif.

Tes browser pagination memakai Chrome lokal dan database SQLite sementara yang terpisah dari data aplikasi:

```powershell
node tests/Browser/pagination.mjs
```

Set `CHROME_PATH` jika lokasi Chrome berbeda. Tes mencakup pergantian daftar di HP/desktop,
state Alpine, catatan, navigasi browser, permintaan bersamaan, dan koneksi gagal.

## Layout Tanya AI

Kolom pertanyaan berada di alur flex terpisah dari area scroll pesan, sehingga tidak menutupi
sapaan atau pesan terakhir. Chat kosong tidak otomatis digulir ke bawah. Header dan navigasi
fitur dipadatkan; judul panjang dipotong secara visual dan tetap tersedia lewat tooltip.
Empat pilihan fitur hanya tampil di halaman materi/folder. Halaman Ringkasan, Tanya AI,
Latihan soal, dan Kartu belajar tidak menampilkan ulang bar fitur; tombol kembali ke materi
tetap tersedia. Pilihan pertanyaan memakai palet sage dan teks gelap, dengan susunan
vertikal yang ringkas di HP. Biru tetap untuk tombol kirim dan fokus input.
Shift+Enter menambah baris; textarea panjang bisa digulir.

```powershell
node tests/Browser/pagination.mjs --chat-layout
```

Tes ini memakai fixture terpisah yang sama, memeriksa enam ukuran viewport, pesan panjang,
pratinjau gambar, dan pertanyaan cepat dengan respons AI tiruan. Screenshot desktop/HP
tersimpan di `storage/framework/testing/chat-layout-*.png`.

## Pengaturan soal

Tombol Latihan soal pada materi dan folder membuka popup `quiz-settings`, tanpa mengubah
ukuran kartu fitur. Popup memakai dialog native dengan fokus keyboard, tombol Batal/Tutup,
dan Escape. Jenis soal dan jumlah 1-30 dikirim melalui form asli, termasuk pilihan file folder.

```powershell
node tests/Browser/pagination.mjs --quiz-dialog
```

Daftar folder pada Materi saya memakai baris ringkas selebar area daftar. Aksi file berada
di sisi kanan, termasuk pada layar laptop. Menu titik tiga memakai Popover API dan posisi
yang dibatasi viewport; pengaturan soal di menu membuka dialog yang sama dengan halaman materi.

```powershell
node tests/Browser/pagination.mjs --library-layout
```
