# Pembuatan ringkasan pertama

Diagnosis 6 November 2026 membedakan waktu pembuatan pertama dari pemakaian
ulang hasil. Pada kegagalan folder terakhir yang diperiksa, proses berlangsung
sekitar 59 detik dan mencatat `AI belum mengembalikan isi ringkasan`.
Ini belum membuktikan timeout jaringan. Respons mentah saat kegagalan tidak
tersimpan; penyebab format persisnya tidak bisa dipastikan.

Folder tersebut berisi 5 file, 143104 karakter. Penghitungan fingerprint sekitar
11 ms dan persiapan prompt dengan HTTP palsu sekitar 69 ms. Keduanya tidak
menjelaskan penantian puluhan detik. Prompt lama meminta 2000-4500 kata dan
respons JSON dengan Markdown panjang di dalam string; format yang tidak cocok
bisa menjadi hasil kosong pada parser JSON lama.

## Perubahan

- Model dan batas konteks tetap; batas output 7500 naik menjadi 8192 token.
- AI mengirim Markdown dengan bagian Ringkasan lengkap, Poin penting, Kesimpulan.
  Parser mempertahankan isi, tabel, kode, dan kompatibilitas JSON lama.
- Prompt meminta seluruh topik, detail penting, serta batasan fakta sumber.
  Target minimum kata dihapus agar pengulangan tidak memperpanjang jawaban.
  Ringkasan tidak lagi membawa instruksi percakapan atau memori chat.
- Streaming hanya antara provider dan server. Satu permintaan AI, tanpa retry
  otomatis atau navigasi baru; pengguna tetap menunggu hasil lengkap.
- Selesai saat `finish_reason=stop`, tanpa menunggu socket ditutup. Output yang
  terpotong, kosong, atau kehilangan koneksi tidak ditandai completed.
- Streaming ringkasan mempunyai deadline total sesuai timeout 9router (maksimal
  300 detik). Socket HTTP menyesuaikan batas baca dengan waktu tersisa. PHP/job
  diberi 360 detik; setelah respons masuk, 60 detik untuk validasi dan penyimpanan.
- Referensi folder hanya mencari file terpilih. Potongan yang sudah bersih tidak
  dibersihkan ulang; pemotongan akhir hanya dilakukan pada referensi pemenang.
- Rendering Markdown menolak HTML mentah dan tautan berbahaya. Pesan gagal tidak
  lagi menyimpulkan provider tidak merespons saat hasilnya mungkin tidak valid.

## Pemeriksaan

`php scripts/diagnose-summary-latency.php` membaca metadata database lokal tanpa
mengubah materi atau ringkasan. HTTP palsu dan preventStrayRequests mencegah
panggilan AI nyata. Tidak mencetak teks dokumen atau kredensial.

`php scripts/check-summary-response.php` mengirim 5 dokumen sintetis berisi total
140000 karakter ke provider yang dikonfigurasi, tanpa query database. Dokumen
mengulang lima paragraf berbeda; ini bukan ukuran kompleksitas materi nyata.
Hasil pengukuran dan jawaban sintetis tersimpan di
`storage/framework/testing/summary-provider-sample-latest.json`.

Uji awal sebelum perubahan selesai 35,83 detik, 2697 kata isi. Uji antara setelah
perubahan selesai 30,6 detik, 849 kata isi. Keduanya berhasil, jadi kegagalan lama
belum berhasil direproduksi. Panjang jawaban berbeda; angka ini tidak membuktikan
percepatan dengan kedalaman setara atau menjamin waktu untuk dokumen pengguna.

Uji setelah prompt akhir dan deadline diterapkan: 44,61 detik, 1173 kata isi.
Uji berikutnya menambahkan pengukuran waktu teks pertama: total 58,94 detik;
potongan pertama diterima 28,65 detik setelah memanggil client; respons selesai
58,92 detik; isi 1046 kata. Persiapan prompt dan parsing di luar client sekitar
0,02 detik. Semua lima topik terbahas dan seluruh bagian berhasil dipisahkan.
Pengukuran ini menunjukkan penantian dominan terjadi selama permintaan endpoint
pada sampel tersebut. Belum membedakan antrean gateway, jaringan, waktu membaca
konteks, dan generasi model. Percepatan konsisten belum terbukti; perbaikan ini
mengurangi kerja berulang dan ketergantungan pada format JSON panjang, bukan
jaminan ringkasan pertama selesai dalam beberapa detik.

Tes regresi meliputi hasil terpotong, reasoning tanpa jawaban, batas waktu total,
parser Markdown/JSON, pemilihan referensi, dan pemakaian ulang hasil. Database tes
terpisah memakai SQLite in-memory; tidak membuat ulang materi pengguna.
