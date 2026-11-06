# Format jawaban chat

Chat pribadi mendeklarasikan kemampuan tampilannya lewat
`GeminiService::chat(..., richResponses: true)` dan
`GeminiService::streamChat(..., richResponses: true)`.
Nilai default `false` menjaga pemanggil dengan renderer lama, termasuk ruang
belajar, tetap memakai arahan Markdown dasar.

AI memilih bentuk penjelasan berdasarkan pertanyaan: tabel untuk perbandingan,
diagram untuk proses/hubungan, grafik untuk data angka, LaTeX untuk rumus,
serta blok kode dengan bahasa dan indentasi. Visual tidak diwajibkan pada tiap
jawaban. Permintaan teks saja atau jawaban singkat tetap dihormati.

Grafik harus memakai data yang tersedia. Contoh hipotetis wajib diberi label
pada penjelasan dan judul grafik; angka, statistik, tren, dan sumber tidak boleh
dibuat-buat. Fitur ini tidak menambahkan akses pencarian web.

## Kontrak keluaran

Diagram memakai pagar `mermaid` dengan tipe flowchart/graph, sequenceDiagram,
mindmap, stateDiagram-v2, classDiagram, erDiagram, gantt, timeline, atau pie.
Label berupa teks; HTML, link/click, konfigurasi, dan CSS kustom tidak diterima.
Aplikasi menentukan tampilan diagram.

Grafik memakai pagar `chart` dan JSON, misalnya:

```json
{
  "type": "bar",
  "title": "Contoh hipotetis",
  "labels": ["A", "B"],
  "datasets": [{"label": "Jumlah", "data": [10, 20]}],
  "xLabel": "Kategori",
  "yLabel": "Jumlah (unit)"
}
```

Tipe: `bar`, `line`, `pie`, `doughnut`. Maksimal 50 label dan 6 dataset.
Setiap dataset berisi angka finite dengan jumlah sama dengan label.
Pie/doughnut memakai satu dataset nonnegatif. JSON tidak menerima fungsi atau
konfigurasi Chart.js langsung.

Rumus mendukung `$...$`, `$$...$$`, `\(...\)`, dan `\[...\]`.
Blok kode hanya ditampilkan, tidak dijalankan.

## Validasi

`NineRouterTest` memeriksa payload chat umum/materi, streaming/nonstreaming,
pilihan format otomatis, batas schema, larangan mengarang angka, dan renderer
lama tanpa arahan visual. Respons tiruan memeriksa pagar kode, separator tabel,
LaTeX, serta spasi/baris kode tetap utuh pada kedua jalur.

Tes payload tidak menjamin model selalu memilih bentuk terbaik atau menghasilkan
sintaks valid. Renderer perlu menangani blok parsial dan keluaran invalid tanpa
menghilangkan penjelasan. Model, batas output 8192, streaming, dan header
`X-9Router-Token-Saver: off` tetap digunakan.

Renderer chat memakai Markdown tersanitasi, KaTeX tanpa trusted commands,
Mermaid strict, dan Chart.js dengan opsi yang ditentukan aplikasi. Konten AI
tidak dijalankan sebagai HTML, JavaScript, atau direktif Alpine. Mermaid tidak
bisa memuat gambar/icon eksternal dari metadata node. Diagram yang belum
lengkap menunggu akhir blok; keluaran invalid menampilkan pesan dan sumbernya.
Visual lengkap digunakan ulang selama paragraf berikutnya masih streaming.
Pustaka jawaban dimuat saat ada jawaban, diagram/grafik dimuat saat diperlukan.

Pemeriksaan browser mencakup Markdown, empat delimiter rumus, diagram/grafik,
salin kode, sanitasi, streaming parsial, cleanup, dan layar 320/390/1440 px:
`node tests/Browser/pagination.mjs --chat-rich`.
Validasi struktur visual: `node --test tests/Browser/chat-visual-validation.test.mjs`.

Pemeriksaan manual provider: `php scripts/check-chat-rich-response.php`.
Script mengirim tiga pertanyaan sintetis tanpa meminta format tertentu,
menyimpan jawaban di `storage/framework/testing/chat-rich-provider.json`, dan
hanya mencetak metrik. Pada uji 5 November 2026, model memilih diagram untuk
siklus air, grafik garis untuk penjualan 10/15/12/22, dan rumus/diagram untuk
persamaan linear. Semua keluaran tersebut berhasil dirender melalui
`node tests/Browser/pagination.mjs --chat-rich --provider-sample`.
Ini bukti sampel, bukan jaminan semua jawaban berikutnya memakai format sama.

`php scripts/check-chat-rich-response.php` menjalankan tiga request sintetis
berurutan ke provider yang dikonfigurasi. Pertanyaan tidak meminta format
eksplisit: siklus air, analisis penjualan bulanan yang disediakan, dan persamaan
linear. Ini pemeriksaan manual yang memakai API, bukan bagian tes otomatis.
Console hanya menampilkan durasi, jumlah kata, deteksi format, dan tipe error.
Jawaban sintetis disimpan di `storage/framework/testing/chat-rich-provider.json`
untuk penilaian isi dan sintaks. Deteksi format bukan penilaian akurasi atau
syarat bahwa semua jawaban wajib berisi visual.
