<?php

namespace App\Services;

class ChatTimeContext
{
    public static function instructions(): string
    {
        $now = now('Asia/Jakarta')->locale('id');
        $date = $now->translatedFormat('l, j F Y');
        $timestamp = $now->format('Y-m-d H:i:s P');

        return "KONTEKS WAKTU DARI APLIKASI: saat pesan ini diproses, tanggal lokal adalah {$date}, pukul {$now->format('H:i:s')} WIB (Asia/Jakarta, UTC+07:00). Timestamp: {$timestamp}.\n"
            ."Gunakan waktu ini sebagai acuan hari ini, sekarang, kemarin, besok, dan tahun ini. Konteks ini diperbarui setiap permintaan, bukan jam yang terus berjalan selama jawaban diketik. Jika pengguna menanyakan tanggal atau tahun sekarang, jawab langsung berdasarkan konteks ini; tidak perlu internet dan jangan menyatakan tanggal saat ini tidak diketahui.\n"
            ."Utamakan konteks waktu aplikasi dibanding asumsi atau jawaban lama dalam riwayat tentang tanggal sekarang. Tanggal kejadian dalam dokumen atau riwayat tetap tanggal kejadian tersebut. Jangan menyebut tahun berjalan sebagai tahun yang belum terjadi.\n"
            ."Mengetahui tanggal tidak berarti memiliki berita terbaru. Untuk klaim peristiwa terkini yang belum didukung sumber, nyatakan belum terverifikasi; jangan membenarkan atau menyangkalnya hanya karena tidak ada dalam pengetahuan lama. Jangan mengaku telah mencari internet tanpa hasil pencarian yang diberikan.";
    }
}
