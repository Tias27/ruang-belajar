# Pakai ulang ringkasan

Tombol Ringkasan membuka hasil completed yang sesuai dengan sumber saat ini.
Tidak membuat baris riwayat baru dan tidak memanggil AI jika hasil masih cocok.
Kecocokan juga memeriksa raw_response._summary_policy. Kebijakan saat ini
menargetkan 45-48% jumlah kata konteks sumber untuk seluruh bagian jawaban.
Hasil dari kebijakan lama disimpan sebagai riwayat, lalu diganti satu kali saat
Ringkas diklik. Hasil baru dipakai ulang seperti biasa.
File baru disiapkan otomatis setelah upload; lihat automatic-summaries.md.
Pembuatan folder atau file lama tetap dimulai saat diminta, dengan halaman
menunggu sampai hasil lengkap siap.

SummaryReuse menyimpan fingerprint dalam raw_response._source_snapshot,
tanpa perubahan schema database. Fingerprint mencakup judul sumber, ID file,
judul file, identitas file, format, dan hash teks yang diekstrak.
Tambahan/penghapusan file dan perubahan isi membatalkan kecocokan.

Pilihan file folder dibandingkan sebagai himpunan: urutan centang tidak
berpengaruh. Pilihan seluruh file saat ini setara dengan Ringkasan folder.
Tambahan file di luar pilihan tidak membatalkan ringkasan file terpilih.

Ringkasan lama tanpa fingerprint diadopsi hanya jika waktu perubahan sumber
tidak melewati waktu pembuatannya dan pilihan file cocok. Dokumen yang dibuat,
diubah, dipindah, atau dihapus menandai ringkasan lama terkait sebagai tidak
layak dipakai ulang sebelum fingerprint pertama disimpan. Riwayat tetap ada.
Perubahan historis sebelum fitur aktif bergantung pada timestamp yang tersedia;
format data lama tidak menyimpan daftar lengkap isi folder saat pembuatan.

Hanya hasil berisi teks yang selesai dapat dipakai ulang. Hasil failed/kosong
bisa dibuat ulang. Reservasi dibuat dalam transaksi dengan lock sumber singkat;
request lain dengan fingerprint yang sama memakai queued/processing terbaru.
Waktu menunggu antrean tidak dianggap proses macet. Processing yang tertinggal
lebih dari enam menit dapat dicoba ulang. Panggilan AI
berlangsung di luar transaksi. Perubahan sumber selama pembuatan membuat hasil
tersebut tidak dipakai ulang pada klik berikutnya.

Validasi: tests/Feature/SummaryReuseTest.php memakai SQLite in-memory dan
mock provider, mencakup file/folder lama, penambahan/penghapusan/pemindahan,
perubahan isi dalam detik yang sama, subset, hak akses, retry, dan perubahan
sumber selama AI berjalan.
