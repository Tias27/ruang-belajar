# Arsitektur Ruang Belajar (Aidosen)

Ruang Belajar adalah aplikasi Laravel monolith untuk membantu mahasiswa belajar dari dokumen kuliah menggunakan Gemini API. Aplikasi tidak memakai SPA framework; seluruh UI memakai Blade, Tailwind CSS (Vite / CDN), dan Alpine.js untuk interaksi interaktif yang ringan dan responsif.

## Layer Sistem

- **Routes**: `routes/web.php` memisahkan route guest, mahasiswa, dan admin.
- **Controllers**: 
  - `Auth`: Menangani registrasi, login, dan reset password.
  - `Student`: Menangani fitur belajar utama seperti `ChatController`, `QuizController`, `FlashcardController`, dan `StudyRoomController`.
  - `Admin`: Menangani panel pengelolaan data oleh pengelola/dosen.
- **Models**: Eloquent model menyimpan relasi antar user, dokumen, hasil AI, chat, kuis, flashcard, log aktivitas, dan study room.
- **Services**: 
  - `GeminiService`: Menangani prompt ringkasan, chat dokumen, soal latihan, flashcard, dan streaming chat.
  - `LearningSourceService`: Menyediakan referensi potongan teks dari materi asli dan jawaban fallback jika API limit tercapai.
  - `DocumentTextExtractor`: Mengekstrak teks awal dari PDF/DOCX/PPTX.
- **Events**: 
  - `StudyRoomMessageSent`: Mengirim pesan lengkap yang sudah tersimpan di database.
  - `StudyRoomMessageChunkGenerated`: Mengirim potongan respons streaming AI secara real-time.
- **Real-time Engine**: Menggunakan Laravel Reverb (WebSockets) dengan Laravel Echo di frontend untuk kehadiran pengguna (*presence channel*) dan siaran langsung pesan.
- **Policies/Middleware**: `RoleMiddleware` membatasi akses admin/mahasiswa. `DocumentPolicy` mendefinisikan kepemilikan dokumen.
- **Views**: Blade di `resources/views` menyediakan dashboard, auth, dokumen, AI output, study rooms (Belajar Bareng), dan admin panel.

## Alur Mahasiswa

1. Mahasiswa register dengan nama, username, role `mahasiswa`, email, dan password.
2. Mahasiswa upload PDF, DOCX, PPTX, atau Gambar (PNG/JPG/GIF/WEBP) maksimal 20 MB.
3. Dokumen disimpan di storage private, metadata masuk ke tabel `documents`.
4. Sistem mencoba mengekstrak teks menggunakan `DocumentTextExtractor` dan menyimpannya ke `documents.extracted_text`.
5. Mahasiswa dapat membuat ringkasan, latihan soal, flashcard, atau chat dokumen secara mandiri.
6. Mahasiswa juga dapat membuat **Study Room (Belajar Bareng)**, membagikan PIN unik kepada mahasiswa lain untuk belajar, bertukar pesan secara real-time, dan didampingi AI Co-Pilot yang merespons pertanyaan di chat.
7. Semua output AI disimpan ke database dan riwayat aktivitas dicatat di `activity_logs`.

## Alur Admin

1. Admin login dengan role `admin`.
2. Admin melihat statistik user, dokumen, ringkasan, flashcard, kuis, percakapan AI, dan ruang belajar aktif.
3. Admin melihat grafik penggunaan harian berdasarkan `activity_logs`.
4. Admin dapat mengubah role user dan menghapus user.
5. Admin dapat melihat semua dokumen dan menghapus dokumen bermasalah.

## ERD

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string username UK
        string role
        string email UK
        string password
        timestamp email_verified_at
        timestamps timestamps
    }

    documents {
        bigint id PK
        bigint user_id FK
        bigint folder_id FK
        string title
        string original_name
        string file_name
        string file_path
        string mime_type
        bigint size
        string extension
        string status
        longtext extracted_text
        text processing_notes
        timestamps timestamps
    }

    document_folders {
        bigint id PK
        bigint user_id FK
        string name
        text description
        timestamps timestamps
    }

    summaries {
        bigint id PK
        bigint document_id FK
        bigint user_id FK
        text short_summary
        longtext full_summary
        json key_points
        text conclusion
        json raw_response
        timestamps timestamps
    }

    flashcards {
        bigint id PK
        bigint document_id FK
        bigint user_id FK
        text front
        text back
        int position
        timestamps timestamps
    }

    quizzes {
        bigint id PK
        bigint document_id FK
        bigint user_id FK
        bigint study_room_id FK
        string title
        int question_count
        timestamps timestamps
    }

    quiz_questions {
        bigint id PK
        bigint quiz_id FK
        text question
        json options
        string correct_answer
        text explanation
        int position
        timestamps timestamps
    }

    chat_sessions {
        bigint id PK
        bigint document_id FK
        bigint user_id FK
        string title
        timestamps timestamps
    }

    chat_messages {
        bigint id PK
        bigint chat_session_id FK
        string role
        longtext content
        json metadata
        timestamps timestamps
    }

    study_rooms {
        bigint id PK
        uuid uuid UK
        bigint host_id FK
        string target_type
        bigint target_id
        string pin UK
        enum status
        json selected_document_ids
        timestamps timestamps
    }

    study_room_messages {
        bigint id PK
        bigint study_room_id FK
        bigint user_id FK
        text message
        boolean is_ai
        json metadata
        timestamps timestamps
    }

    study_room_users {
        bigint id PK
        bigint study_room_id FK
        bigint user_id FK
        timestamps timestamps
    }

    activity_logs {
        bigint id PK
        bigint user_id FK
        string action
        string subject_type
        bigint subject_id
        json metadata
        timestamps timestamps
    }

    users ||--o{ documents : owns
    users ||--o{ document_folders : owns
    document_folders ||--o{ documents : contains
    users ||--o{ summaries : creates
    users ||--o{ flashcards : creates
    users ||--o{ quizzes : creates
    users ||--o{ chat_sessions : starts
    users ||--o{ activity_logs : triggers
    users ||--o{ study_rooms : hosts
    users ||--o{ study_room_messages : sends
    users ||--o{ study_room_users : joins
    
    documents ||--o{ summaries : has
    documents ||--o{ flashcards : has
    documents ||--o{ quizzes : has
    documents ||--o{ chat_sessions : has
    
    quizzes ||--o{ quiz_questions : contains
    chat_sessions ||--o{ chat_messages : contains
    
    study_rooms ||--o{ study_room_messages : contains
    study_rooms ||--o{ study_room_users : has
    study_rooms ||--o{ quizzes : has
```

## Konfigurasi Penting

- `GEMINI_API_KEY`: Kunci akses API Gemini untuk pemrosesan kecerdasan buatan.
- `GEMINI_MODEL`: Model yang digunakan (default: `gemini-2.5-flash`).
- `STUDY_ROOM_REALTIME`: Aktifkan `true` untuk mengizinkan sinkronisasi pesan real-time menggunakan Laravel Reverb / Pusher.
- `VIEW_COMPILED_PATH`: Diarahkan ke folder compiled view yang writable di Windows/Laragon.
