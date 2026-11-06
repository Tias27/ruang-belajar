# Kualitas jawaban chat

## Perbaikan terverifikasi: penghemat token 9router

Request langsung dengan model dan pertanyaan yang sama menghasilkan gaya pendek
ketika menggunakan pengaturan server, lalu kembali menggunakan kalimat utuh dan
paragraf penjelasan ketika header `X-9Router-Token-Saver: off` dikirim.
Percobaan nonstream menghasilkan 196/171 token output tanpa header, dibandingkan
783/546 token dengan header. Ini sampel perilaku, bukan target panjang jawaban.

Source publik 9router menyediakan opt-out per request di
[`chatCore.js`](https://github.com/decolua/9router/blob/master/open-sse/handlers/chatCore.js).
Header tersebut menonaktifkan beberapa penghemat token, termasuk penyisipan gaya
pendek Caveman/Ponytail, tanpa mengubah konfigurasi global gateway. Nilai persis
pengaturan server belum dibaca karena dashboard memerlukan login. Hasil A/B
mengisolasi pengaruh jalur penghemat token; tidak membuktikan semua fiturnya aktif.

NineRouterClient kini mengirim header itu untuk streaming maupun nonstreaming,
termasuk ringkasan, soal, dan flashcard agar instruksi aplikasi tidak ditimpa
gaya penghemat token. Tidak mengubah model, jumlah soal, atau format JSON.
Tes integrasi memastikan header ikut terkirim pada kedua jalur.

Uji akhir melalui `GeminiService::streamChat` dengan pertanyaan laut yang sama
menghasilkan sekitar 403 kata berupa kalimat utuh, penjelasan komponen dan proses,
serta penutup. Ini membuktikan perubahan bekerja pada jalur chat aplikasi,
bukan hanya script request langsung. Akurasi fakta tetap perlu dinilai terpisah.

`php scripts/check-chat-response.php --token-saver-off` dapat dipakai untuk
membandingkan dengan mode default script diagnostik.

## Pengaturan jawaban

Chat bebas, chat materi, dan chat ruang belajar memakai batas output 8192 token,
batas yang saat ini diterapkan client 9router (sebelumnya 1100 lalu 4096).
Ini batas teknis aplikasi, bukan klaim batas maksimum model upstream.
Model 9router dan batas konteks materi tetap.

Chat bebas sebelumnya melewati instruksi chat dan hanya memasukkan pertanyaan
terakhir beserta riwayat. Sekarang instruksi bersama ikut masuk: jawaban alami,
jelas, berisi, kalimat utuh, dan panjang yang mengikuti kebutuhan pengguna.
Patokan 300-700 kata, jumlah kalimat per poin, dan susunan tutor wajib dihapus
sesuai permintaan pengguna. Chat materi tetap menerima konteks dan memori,
namun tidak lagi dipaksa memakai aturan presentasi untuk generasi materi.
Sistem 9router memakai arahan singkat tanpa kuota kata atau format wajib.

Mengikuti perbandingan dalam `responai.md`, jawaban penjelasan diarahkan dari
pengertian sederhana menuju bagian penting dan cara kerjanya. Istilah teknis
diberi arti; contoh diterangkan hubungan atau prosesnya, bukan hanya disebutkan.
Pembahasan memakai kalimat utuh dan paragraf yang berkembang. Permintaan singkat
tetap dihormati dan tidak ada kewajiban memperpanjang sapaan atau fakta sederhana.

Uji langsung sebelum perbaikan header memakai pertanyaan ekosistem dari perbandingan
pengguna. Respons mencakup definisi, komponen, proses, dan contoh hubungan, tetapi
bahasanya masih berupa catatan pendek. Percobaan tambahan dengan contoh paragraf
alami pada request uji juga belum memperbaiki gaya tersebut; contoh itu tidak
ditambahkan ke konfigurasi aplikasi. Kualitas hasil belum memenuhi contoh acuan
pengguna secara konsisten, meskipun instruksi sudah diteruskan dengan benar.

Pengujian integrasi memeriksa kedua jalur (streaming dan biasa), konteks,
riwayat, model, serta budget output. Pengujian ini tidak menilai kualitas
semantik model; respons provider dipalsukan pada tes otomatis.

Tes langsung sebelum penyederhanaan prompt, dengan pertanyaan sintetis tentang cara belajar dan kecepatan
sepeda menghasilkan langkah hitungan yang benar (18 km/jam atau 5 m/s).
Namun jawaban konsep masih bervariasi, sekitar 125-258 kata dan beberapa
masih memakai kalimat terpotong meskipun instruksi meminta kalimat utuh.
Saat pengujian tersebut, kepatuhan model terhadap gaya dan kedalaman jawaban belum
konsisten. Hasil itu mendahului temuan penghemat token. Jangan mengklaim target
panjang atau perbaikan kualitas terjamin dari tes payload saja.

Tidak ada retry otomatis untuk memperpanjang jawaban, agar tidak menggandakan
panggilan provider dan menunda streaming. Jawaban lebih panjang dapat memerlukan
waktu generasi lebih lama. Ringkasan, soal, dan flashcard tidak diubah.

## Diagnosis respons mentah 9router

`php scripts/check-chat-response.php` mengirim dua request sintetis langsung
ke endpoint yang dikonfigurasi, tanpa melewati GeminiService, riwayat aplikasi,
memori, atau renderer browser. Perintah ini memakai API dan menghasilkan biaya
sesuai pengaturan provider. Tidak mencetak kredensial atau data pengguna.

Pertanyaan: "Berikan penjelasan apa itu laut dan terbuat dari apa."
Keduanya memakai model `ag/gemini-3.8-flash-medium`, max_tokens 8192,
temperature 0.4, dan stream false.

| Request | Input yang dikirim | prompt_tokens dilaporkan | completion_tokens | finish_reason |
| --- | --- | ---: | ---: | --- |
| plain_user | Satu pesan pengguna, tanpa system prompt | 2939 | 196 | stop |
| separate_roles | System prompt singkat dan pesan pengguna terpisah | 3133 | 171 | stop |

Kedua respons mentah masih menggunakan kalimat terpotong, misalnya
"Laut kumpulan air asin luas di bumi. Sambung ke samudra."
Dengan demikian gaya ini sudah muncul pada keluaran endpoint, bukan hanya
setelah pemrosesan atau rendering aplikasi. `stop` menunjukkan respons tidak
berhenti karena batas output pada sampel tersebut.

Respons menyebut model `gemini-3.8-flash-n`, sementara daftar `/v1/models`
mencantumkan ID permintaan dengan pemilik `ag`. Perbedaan nama dapat merupakan
pemetaan alias normal dan belum membuktikan kesalahan model.

Jumlah prompt_tokens jauh melebihi ukuran pesan singkat yang dikirim.
Ini mengindikasikan overhead/konteks tambahan atau perbedaan penghitungan
provider; belum membuktikan adanya instruksi gaya tertentu. Pengaturan
gateway/provider perlu diperiksa sebelum mengubah aplikasi lagi.
Dashboard 9router mengarahkan ke halaman login, sehingga pengaturan internal
belum diperiksa. Tidak ada perubahan pada 9router atau pergantian model.
