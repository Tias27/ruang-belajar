# Ruang Belajar - konsep UI

Prototipe visual untuk review, terpisah dari aplikasi Laravel.

Buka `index.html` di browser. Semua materi dan hasil belajar merupakan contoh.
Tidak ada upload, penyimpanan akun, atau permintaan AI sungguhan.
Preview sekarang dibuka dari halaman awal sebelum login. Beranda belajar tetap
bisa diakses melalui tautan contoh atau hash `#home`.

Untuk melihat versi terbaru dari PowerShell di folder proyek:

```powershell
Start-Process ".\docs\ui-concept\index.html"
```

Jika preview sudah terbuka, tekan `Ctrl+F5` dan klik logo untuk kembali ke halaman
awal. Animasi berjalan di HTML; PNG dan SVG ekspor merupakan gambar statis.

Untuk langsung mereview bagian dalam tanpa melewati form login:

```powershell
Start-Process ".\docs\ui-concept\workspace.html"
```

File ini membuka `index.html#home`. Semua layar memakai prototipe yang sama.

## Arah desain

- Latar putih hangat, teks arang, satu aksen biru untuk aksi dan pilihan aktif.
- Teks utama memakai hitam lembut, bacaan abu-abu gelap, dan keterangan abu-abu.
  Teks pada tombol biru tetap putih. Hijau dipakai untuk ilustrasi, latar, ikon,
  dan status; tidak lagi menjadi warna semua tulisan.
- Revisi 02 memakai Plus Jakarta Sans, sudut komponen yang lebih lembut, dan
  bahasa percakapan seperti "Hai, Nadia!" dan "Lanjut yang tadi?".
- Halaman awal, login, dan daftar kini memakai ilustrasi taman: latar hijau lembut,
  ruang putih hangat, dan tombol biru. Komposisi mengikuti arah referensi hutan
  yang tenang; ilustrasi SVG dibuat khusus untuk prototipe ini.
- Judul "Belajar boleh pelan-pelan." menjadi fokus halaman awal. Contoh fitur
  interaktif berada tepat setelah ilustrasi supaya manfaat web tetap jelas.
- Bagian pembuka desktop mengikuti tinggi layar (sekitar 560-740 px), dengan
  judul dan jarak yang lebih ringkas pada laptop pendek supaya karakter terlihat
  tanpa menggulir halaman. Tampilan ponsel tetap memakai susunan vertikal.
- Beranda mengutamakan materi terakhir, bukan daftar semua fitur sekaligus.
- Materi menjadi konteks tetap untuk Ringkasan, Tanya AI, Latihan soal, dan Kartu belajar.
- Navigasi dan nama fitur konsisten pada desktop dan ponsel.
- Garis pemisah dan ruang kosong menggantikan gradasi, bayangan besar, dan kartu berlapis.

## Bagian dalam

`workspace.css` meneruskan warna kertas hangat, hijau lembut, dan tombol biru ke
beranda, materi, halaman belajar, riwayat, belajar bareng, dan panduan. Aturan
dibatasi oleh `.workspace-view` agar halaman pengunjung tetap seperti yang direview.

- Beranda menonjolkan materi terakhir, ilustrasi kelinci yang bergerak, dan empat
  pintasan belajar dengan nama serta kegunaan yang jelas.
- **Tambah materi** membuka dialog pilihan file. **Materi saya** menyediakan
  pencarian, filter file/folder, dan baris materi dengan aksi **Buka**.
- Empat tab belajar mendapat keterangan singkat. Di ponsel, tab tersusun dua
  kolom agar label tetap terbaca. Isi bacaan memakai ukuran teks lebih besar.
- Catatan pribadi memakai panel seperti kertas catatan. Latihan, kartu, chat,
  riwayat, dan belajar bareng mengikuti warna serta bentuk komponen yang sama.
- Tautan **Lihat halaman awal** memudahkan perpindahan saat review.

Materi contoh memiliki tiga detail lengkap: Biologi, Ekonomi, dan Statistika.
Lima entri lain masih membuka contoh Biologi dengan pemberitahuan. Catatan hanya
bertahan selama sesi preview. Perubahan ini belum terhubung ke aplikasi Laravel.

### Belajar bareng

Halaman `#rooms` memakai ilustrasi kelinci dan beruang belajar satu meja, form
kode yang menyatu dengan ilustrasi, serta pilihan materi berbentuk buku kecil.
Gerak halus mengikuti tombol jeda dan preferensi kurangi gerak yang sama.

`rooms.css` mengatur tampilan halaman dan dialog; `rooms.js` menyediakan alur
contoh masuk/bikin ruang. Kode wajib enam angka; kode yang ditempel dengan spasi
atau tanda hubung dapat dibaca tanpa memecah input menjadi enam kotak.
Memilih materi membuka dialog dengan materi itu terpilih. Lanjutannya menampilkan
kode contoh dan tautan ke tab Tanya AI untuk materi yang sama. Form masuk hanya
memeriksa format kode dan membuka contoh Biologi, tidak mencari ruang sungguhan.
Tidak ada ruang yang dibuat, undangan yang dikirim, atau koneksi antarpengguna.

## Gerak ilustrasi

Awan bergeser, pohon dan tanaman bergoyang, kelinci berkedip, halaman buku bergerak,
air beriak, uap cangkir naik, dan kupu-kupu mengepak. Geraknya pelan tanpa parallax.
Ilustrasi yang sama hadir pada halaman awal, login, dan daftar.

Kontrol gerak memakai ikon jeda/putar tanpa tulisan. Label aksesibel tetap tersedia
untuk pembaca layar. Pilihan jeda berlaku antarhalaman selama preview terbuka.
Pengaturan perangkat `prefers-reduced-motion` menonaktifkan animasi dan kontrolnya.
Ilustrasi di luar layar atau tab browser yang tidak aktif ikut dijeda.

`garden.js` menyimpan lapisan ilustrasi dan kontrol gerak. `garden.css` mengatur
komposisi responsif serta animasi. Perubahan ini hanya berada di prototipe.

## Alur review

1. Halaman awal: coba tab contoh fitur atau klik **Mulai belajar**.
2. Daftar: coba pesan validasi dan tautan **Masuk aja**.
3. Login: coba tombol lihat kata sandi, hitungan verifikasi, dan **Lupa kata sandi**.
4. Pakai data contoh untuk melewati form, atau klik tautan contoh beranda pada login.
5. Beranda: klik **Lanjut belajar**, lalu pindah di antara empat cara belajar.
6. Coba soal contoh, balik kartu belajar, atau cari file di **Materi saya**.
7. Ubah lebar browser untuk melihat navigasi ponsel.

Form hanya mensimulasikan alur. Nama pengguna dan kata sandi tidak dikirim atau
disimpan; form yang valid menampilkan beranda contoh dan mengosongkan input.
Tautan reset tidak mengirim email. Login sesungguhnya tetap ditangani Laravel.

## Figma

File SVG di folder ini dapat diimpor ke Figma sebagai referensi visual berlapis.
Teks tetap berupa elemen teks di SVG; cara Figma mengimpor teks bergantung pada
pengimpor dan font yang tersedia. SVG bukan komponen Figma atau auto-layout.
Font: Plus Jakarta Sans untuk judul dan antarmuka, disimpan lokal di `fonts/`
beserta lisensi OFL. SVG menyertakan font untuk preview browser. Saat impor ke
Figma, pasang font dari folder tersebut jika Figma melaporkan font hilang.

PNG tersedia untuk review cepat. Belum ada file yang dikirim ke akun Figma.

| Layar | Desktop | Ponsel |
| --- | --- | --- |
| Beranda | `01-beranda-desktop.png` / `.svg` | `04-beranda-mobile.png` / `.svg` |
| Materi saya | `02-materi-desktop.png` / `.svg` | Buka prototipe pada layar kecil |
| Detail belajar | `03-belajar-desktop.png` / `.svg` | `05-belajar-mobile.png` / `.svg` |
| Halaman awal | `06-halaman-awal-desktop.png` / `.svg` | `09-halaman-awal-mobile.png` / `.svg` |
| Login | `07-login-desktop.png` / `.svg` | `10-login-mobile.png` / `.svg` |
| Daftar | `08-daftar-desktop.png` / `.svg` | `11-daftar-mobile.png` / `.svg` |
| Riwayat | `12-riwayat-desktop.png` / `.svg` | Buka prototipe pada layar kecil |
| Belajar bareng | `13-belajar-bareng-desktop.png` / `.svg` | `19-belajar-bareng-mobile.png` / `.svg` |
| Tanya AI | `14-tanya-ai-desktop.png` / `.svg` | Buka tab Tanya AI |
| Latihan soal | `15-latihan-soal-desktop.png` / `.svg` | Buka tab Latihan soal |
| Kartu belajar | `16-kartu-belajar-desktop.png` / `.svg` | Buka tab Kartu belajar |
| Materi saya (ponsel penuh) | - | `17-materi-mobile.png` / `.svg` |
| Panduan | `18-panduan-desktop.png` / `.svg` | Buka prototipe pada layar kecil |

SVG mempertahankan bentuk dan teks terpisah; rotasi dekorasi kertas disederhanakan.
Ekspor ponsel menunjukkan viewport pertama, kecuali Belajar bareng, daftar materi, dan form login/daftar yang
ditampilkan penuh. Prototipe HTML menyediakan halaman lengkap yang bisa digulir.

## Keputusan yang perlu direview

- Nama **Riwayat belajar** mengusulkan satu tempat untuk ringkasan, percakapan,
  dan latihan. Aplikasi saat ini masih memiliki halaman riwayat percakapan.
- Detail materi menggabungkan empat fitur sebagai tab. Ini konsep alur baru;
  belum mengubah route atau layanan aplikasi.
- Identitas tulisan dan ikon buku adalah usulan tampilan, bukan perubahan logo
  pada aplikasi.

## Verifikasi

Preview diperiksa di Chrome pada lebar 360, 390, 768, 1024, dan 1440 px.
Navigasi, pencarian, filter folder, tab dengan keyboard, soal, kartu, dan catatan
selama sesi telah dicoba. Tidak ada pengujian atau perubahan backend Laravel.
Halaman pengunjung diperiksa pada lima lebar layar yang sama; navigasi,
validasi form, tombol kata sandi, tab contoh, dan alur reset lolos 38 pemeriksaan
browser tanpa kesalahan JavaScript.
Revisi taman lolos 66 pemeriksaan browser: gerak lapisan, jeda/putar, preferensi
kurangi gerak, jeda saat keluar layar, referensi SVG, tiga halaman pada lima lebar
layar, tab contoh, dan alur login. Tidak ditemukan overflow horizontal.
Revisi bagian dalam lolos 133 pemeriksaan browser: sembilan tampilan pada lima
lebar layar, label tab, pintasan fitur, pencarian/filter, jawaban latihan, kartu,
catatan sesi, dialog, formulir ruang, gerak/jeda ilustrasi, dan pintu preview
langsung. Tidak ditemukan error JavaScript atau overflow horizontal.
Revisi Belajar bareng lolos 49 pemeriksaan browser: halaman dan dialog pada lima
lebar layar, validasi kode, tempel kode berformat, pilihan materi, navigasi ke
materi terkait, penutupan dialog, gerak/jeda, dan preferensi kurangi gerak.

Untuk memperbarui ekspor di Windows dengan Chrome terpasang:

```powershell
node docs/ui-concept/export-preview.mjs
```

Script memakai profil Chrome sementara dan port lokal 9334.
Tambahkan `--guest` untuk mengekspor hanya halaman awal, login, dan daftar.
Tambahkan `--workspace` untuk mengekspor hanya halaman dalam.
Tambahkan `--rooms` untuk mengekspor hanya Belajar bareng (desktop dan ponsel).
