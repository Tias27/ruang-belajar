# CampusGPT Architecture

CampusGPT adalah aplikasi Laravel 12 monolith untuk membantu mahasiswa belajar dari dokumen kuliah menggunakan Gemini API. Aplikasi tidak memakai SPA framework; seluruh UI memakai Blade, Tailwind CSS CDN, dan Alpine.js untuk interaksi ringan.

## Layer Sistem

- **Routes**: `routes/web.php` memisahkan route guest, mahasiswa, dan admin.
- **Controllers**: `app/Http/Controllers/Auth`, `Student`, dan `Admin` menangani request web.
- **Models**: Eloquent model menyimpan relasi antar user, dokumen, hasil AI, chat, dan log aktivitas.
- **Services**: `GeminiService` menangani prompt ringkasan, chat dokumen, soal, dan flashcard. `DocumentTextExtractor` mengekstrak teks awal dari PDF/DOCX/PPTX.
- **Repositories**: `DocumentRepository` disediakan untuk query dokumen yang dapat dikembangkan.
- **Policies/Middleware**: `RoleMiddleware` membatasi akses admin/mahasiswa. `DocumentPolicy` mendefinisikan kepemilikan dokumen.
- **Views**: Blade di `resources/views` menyediakan dashboard, auth, dokumen, AI output, dan admin panel.

## Alur Mahasiswa

1. Mahasiswa register dengan nama, NIM, program studi, angkatan, email, dan password.
2. Mahasiswa upload PDF, DOCX, atau PPTX maksimal 20 MB.
3. Dokumen disimpan di storage, metadata masuk ke tabel `documents`.
4. Sistem mencoba mengekstrak teks dan menyimpannya ke `documents.extracted_text`.
5. Mahasiswa dapat membuat ringkasan, soal, flashcard, dan chat dokumen.
6. Semua output AI disimpan ke database dan riwayat aktivitas dicatat di `activity_logs`.

## Alur Admin

1. Admin login dengan role `admin`.
2. Admin melihat statistik user, dokumen, ringkasan, flashcard, soal, dan percakapan AI.
3. Admin melihat grafik penggunaan harian berdasarkan `activity_logs`.
4. Admin dapat mengubah role user dan menghapus user.
5. Admin dapat melihat semua dokumen dan menghapus dokumen bermasalah.

## ERD

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string nim UK
        string program_studi
        year angkatan
        string role
        string email UK
        string password
        timestamp email_verified_at
        timestamps timestamps
    }

    documents {
        bigint id PK
        bigint user_id FK
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
    users ||--o{ summaries : creates
    users ||--o{ flashcards : creates
    users ||--o{ quizzes : creates
    users ||--o{ chat_sessions : starts
    users ||--o{ activity_logs : triggers
    documents ||--o{ summaries : has
    documents ||--o{ flashcards : has
    documents ||--o{ quizzes : has
    quizzes ||--o{ quiz_questions : contains
    documents ||--o{ chat_sessions : has
    chat_sessions ||--o{ chat_messages : contains
```

## Konfigurasi Penting

- `GEMINI_API_KEY` wajib diisi untuk fitur AI.
- `GEMINI_MODEL` default: `gemini-2.5-flash`.
- `VIEW_COMPILED_PATH` diarahkan ke folder compiled view yang writable di Windows/Laragon.
- PHP production disarankan 8.3+ dengan ekstensi `fileinfo`, `pdo_mysql`, `zip`, `mbstring`, `curl`, dan `openssl`.
