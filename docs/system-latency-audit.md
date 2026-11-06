# Peta sistem dan pemeriksaan latensi

Tanggal pemeriksaan: 5 November 2026. Lingkup: source lokal aktif, alur utama
fitur, dependensi HTTP, dan request sintetis melalui endpoint AI terkonfigurasi.
Ini bukan audit menyeluruh keamanan atau pengukuran server hosting.

## Alur utama

| Bagian | Alur | Hubungan dengan waktu chat |
| --- | --- | --- |
| Login dan profil | Session Laravel, role middleware, captcha, pembatasan percobaan login | Chat memakai sesi yang sudah aktif, tidak mengulang proses login |
| Dashboard dan rak | Query SQL, gabungan file/folder, pagination | Request terpisah dari chat |
| Upload | Simpan file, ekstrak PDF/Office/OCR, simpan extracted_text | Ekstraksi terjadi saat upload, bukan setiap pertanyaan |
| Materi dan folder | Ambil extracted_text, batasi dokumen menurut pilihan | Folder besar bisa menambah query dan pemrosesan konteks |
| Chat pribadi | Validasi, 10 pesan terakhir, simpan pertanyaan, konteks dan memori, stream AI, sumber, simpan jawaban, antrekan memori | Tahap sebelum token pertama berbeda dari tahap setelah jawaban |
| Ruang belajar | Validasi anggota, simpan pesan, broadcast jika aktif, stream AI, broadcast potongan dan hasil | Broadcast sinkron dapat menambah waktu khusus ruang belajar |
| Ringkasan/soal/flashcard | Controller menjalankan job sinkron, panggilan JSON AI, simpan hasil, redirect | Tetap menunggu sesuai pilihan produk |
| Nilai dan kartu | Simpan jawaban/kemajuan; esai dapat meminta penilaian AI | Tidak menjadi langkah dalam chat pribadi |
| Catatan | Simpan berdasarkan pemilik dan sumber materi | Tidak memanggil AI |
| Memori | Job database pada queue ai-memory, satu worker, lock per pengguna/sumber | Tidak ditunggu chat, tetapi tetap merupakan request AI terpisah yang dapat bersamaan dengan request berikutnya |
| Admin | Query statistik, pengguna, materi; halaman pendapatan memiliki HTTP kurs | Tidak dijalankan oleh endpoint chat |

Frontend chat memakai fetch POST dan ReadableStream. Tidak ditemukan sleep atau
timer yang sengaja menunda tampilan jawaban. Alpine memperbarui konten setiap
chunk. Sumber jawaban dihitung setelah stream selesai, bukan sebelum token awal.

## Transport dan pengukuran

Guzzle memilih PHP StreamHandler untuk stream=true, meskipun ekstensi cURL ada.
Karena itu hasil lewat NineRouterClient saja tidak cukup untuk menyimpulkan
bahwa masalah berada di server 9router.

Perbandingan dilakukan dengan payload sintetis identik yang ditangkap dari
builder request aplikasi, memakai urutan app/cURL/cURL/app. API key dan isi
respons tidak dicetak. Model dan batas token tetap sama.

| Jalur | Header pertama | Teks pertama | Sampai EOF |
| --- | ---: | ---: | ---: |
| HTTP aplikasi 1 | 5.016 s | 5.016 s | 11.044 s |
| cURL langsung 1 | 3.028 s | 3.045 s | 3.252 s |
| cURL langsung 2 | 6.930 s | 6.931 s | 7.219 s |
| HTTP aplikasi 2 | 6.956 s | 7.215 s | 13.675 s |

Koneksi TCP cURL 26-37 ms. Nilai di atas sampel terpisah, bukan benchmark yang
membuktikan selisih tetap antartransport. Jeda awal juga terjadi tanpa controller,
database materi, frontend, atau cache aplikasi. Belum dapat dipisahkan antara
jaringan, gateway 9router, penjadwalan upstream, dan pemrosesan model.

## Bug terkonfirmasi dan perbaikan

Parser sebelumnya mencatat finish_reason tetapi terus membaca sampai [DONE]
atau EOF. Pada dua sampel HTTP aplikasi, finish_reason=stop tiba sekitar
5.017 s dan 8.034 s, sedangkan EOF baru sekitar 11.044 s dan 13.675 s.
Ini menahan penyimpanan hasil, event done ke browser, serta penghentian loading.

NineRouterClient kini berhenti setelah finish_reason diterima, sesudah meneruskan
teks pada event terakhir, lalu menutup body. Tes PumpStream memastikan tidak ada
pembacaan jaringan lagi sesudah tanda selesai. Penanganan stream terputus sebelum
tanda selesai tetap diuji. Perbaikan ini menghilangkan penantian di akhir,
bukan menjamin teks pertama tiba seketika.

## Hal yang tetap perlu dibedakan

- Konteks dokumen bisa sampai ratusan ribu karakter; cache lokal tidak menghapus
  waktu membaca input pada model. Batas tidak diubah.
- Memori menggunakan endpoint/model yang sama. Perebutan kapasitas dengan chat
  berikutnya mungkin terjadi, tetapi belum terbukti dari pengukuran ini.
- Broadcast sinkron hanya berlaku pada ruang belajar jika realtime aktif.
- output_buffering CLI lokal bernilai 0 dan zlib tidak aktif. Ini tidak membuktikan
  konfigurasi PHP web server, reverse proxy, atau hosting sama.
- Hosting, isi dokumen pengguna, dan request browser pengguna belum diukur.

## Mengulang diagnosis

`php scripts/compare-ai-transport.php` membandingkan HTTP mentah aplikasi hingga
EOF dengan cURL langsung. Mode ini sengaja tetap membaca sampai EOF untuk
membedakan tanda selesai dari penutupan koneksi.

`php scripts/compare-ai-transport.php --client-only` mengukur client produksi
yang sudah diperbaiki. Kedua perintah memakai materi sintetis, tidak membaca
dokumen pengguna. Setiap eksekusi mengirim request AI nyata.
