<?php

return [
    'accepted' => ':attribute harus diterima.',
    'array' => ':attribute harus berupa daftar.',
    'confirmed' => 'Konfirmasi :attribute tidak sama.',
    'current_password' => 'Kata sandi saat ini tidak sesuai.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'file' => ':attribute harus berupa file.',
    'in' => ':attribute yang dipilih tidak valid.',
    'max' => [
        'array' => ':attribute maksimal berisi :max item.',
        'file' => ':attribute maksimal :max KB.',
        'numeric' => ':attribute maksimal :max.',
        'string' => ':attribute maksimal :max karakter.',
    ],
    'mimes' => ':attribute harus berupa file: :values.',
    'min' => [
        'array' => ':attribute minimal berisi :min item.',
        'file' => ':attribute minimal :min KB.',
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'regex' => 'Format :attribute belum sesuai.',
    'required' => ':attribute wajib diisi.',
    'required_if' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah dipakai.',

    'attributes' => [
        'email' => 'email',
        'username' => 'nama pengguna',
        'password' => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
        'current_password' => 'kata sandi saat ini',
        'program_studi' => 'jenjang atau jurusan',
        'folder_name' => 'nama folder',
        'folder_description' => 'catatan folder',
        'title' => 'judul',
        'files' => 'file materi',
        'files.*' => 'file materi',
        'question' => 'pertanyaan',
    ],
];
