# Pencarian web chat

Chat memakai tool calling pada koneksi 9router saat `WEB_SEARCH_ENABLED=true`
dan `TAVILY_API_KEY` terisi. Model memilih `search_web` atau `read_web` sesuai
pertanyaan, riwayat, dan gambar. Tidak ada classifier/request model tambahan
untuk pertanyaan yang langsung dijawab. Sapaan dan waktu tetap memakai jalur lokal.
Ringkasan, soal, dan flashcard tidak menggunakan tool web ini.

`search_web` mencari maksimal lima hasil (basic), lalu membaca tiga halaman
utama melalui Extract. `read_web` membaca maksimal tiga URL publik. Hanya kata
kunci atau URL yang dipilih dikirim ke layanan pencarian; instruksi model melarang
pengiriman dokumen privat dan kredensial. Validasi menolak pola rahasia umum,
URL lokal, alamat IP privat, protokol selain HTTP(S), userinfo, dan port khusus.
Aplikasi tidak mengambil URL halaman secara langsung; pembacaan melalui Tavily.

Maksimal dua pemanggilan tool, tiga putaran model, dan deadline percakapan tool
180 detik. Request pencarian/extract memiliki timeout terpisah 5-20 detik, tanpa
retry otomatis. Query identik dalam satu jawaban tidak dikirim lagi. Tidak ada
cache lintas pengguna atau percakapan. Pencarian dan Extract memakai kredit
layanan sesuai paket; UI tidak menampilkan nama layanan/model atau detail error.

Hasil halaman diperlakukan sebagai referensi tidak tepercaya, bukan instruksi.
Model diminta memeriksa tanggal dan membedakan dugaan dari putusan. Sitasi
Markdown ditautkan ke sumber; metadata `web_sources` menyimpan judul, URL,
tanggal terbit bila tersedia, waktu akses, dan status halaman dibaca/cuplikan.
Metadata tidak menyimpan isi halaman. Daftar sumber dapat dibuka setelah refresh.
Sitasi dan penilaian kredibilitas tetap bergantung pada model; bukan jaminan
setiap klaim benar. Halaman login/paywall/tidak terjangkau dapat gagal dibaca.

Setup server:

```dotenv
WEB_SEARCH_ENABLED=true
TAVILY_API_KEY=
TAVILY_TIMEOUT=15
TAVILY_CA_BUNDLE=
```

Jalankan `php artisan config:clear` setelah mengubah konfigurasi dan muat ulang
proses aplikasi persisten bila digunakan. Key hanya berada di `.env` server,
tidak di JavaScript, repositori, URL, atau error publik.

PHP lokal yang belum memiliki CA terpercaya memakai bundle dari
`https://curl.se/ca/cacert.pem`, disimpan di `storage/app/certs/cacert.pem` dengan
`TAVILY_CA_BUNDLE=storage/app/certs/cacert.pem`. Bundle bukan secret, tetapi file
lokal ini diabaikan Git; pada server lain gunakan CA sistem atau sediakan bundle
terpercaya yang diperbarui. Jangan menonaktifkan verifikasi HTTPS.

Validasi: `WebChatTest`, pengujian persistensi dalam `ChatMaterialsTest`, dan
`node tests/Browser/pagination.mjs --web-chat`. Semua uji otomatis memakai API
palsu dan data terisolasi. Uji nyata 6 November 2026: pertanyaan umum tanpa
pencarian; permintaan sumber karhutla menghasilkan lima sumber, pembacaan
halaman, dan sitasi. Durasi satu sampel pencarian sekitar 28 detik, bukan SLA.
