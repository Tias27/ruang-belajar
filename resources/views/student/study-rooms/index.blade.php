<x-app-layout title="Belajar bareng">
    @push('styles')
        <link rel="stylesheet" href="{{ asset('ui/rooms-live.css') }}?v={{ filemtime(public_path('ui/rooms-live.css')) }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('ui/rooms-live.js') }}?v={{ filemtime(public_path('ui/rooms-live.js')) }}" defer></script>
    @endpush

    <div class="rooms-live">
        <div class="page-heading rooms-heading">
            <div><h1>Belajar bareng</h1><p class="subtitle">Kadang, penjelasan teman bikin semuanya lebih masuk akal.</p></div>
            <span class="rooms-heading-note"><span data-icon="book-open"></span>Bawa materi. Bagi cara paham.</span>
        </div>

        <div class="rooms-lounge">
            <section class="room-story" aria-labelledby="room-story-title">
                <div class="room-story-copy"><h2 id="room-story-title">Satu meja<br>Banyak cara paham</h2><p>Ada yang jago jelasin, ada yang berani nanya.<br>Ketemu di sini, belajar bareng.</p></div>
                <div class="room-illustration" data-motion-scene aria-hidden="true">
                    <svg class="garden-art room-art" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 660 300" fill="none">
                  <path d="M0 220c103-45 183-31 282-7 113-42 239-41 378-4v91H0Z" fill="#cdddc3"/>
                  <path d="M0 273c147-25 257-19 343-3 117-19 219-16 317 4v26H0Z" fill="#bdcfb0"/>
                  <g class="garden-cloud" transform="translate(450 45)"><path d="M0 15c7-9 17-10 26-7 2-17 25-20 33-6 16-5 29 3 29 14 13-2 23 2 26 7H0Z" fill="#f7f9ee"/></g>
                  <path d="m608 183-3-96 16-2 2 97 9 10c-17 4-26 3-36-1Z" fill="#b6b28e" stroke="#6d866a" stroke-width="1.6"/>
                  <g class="garden-canopy"><path d="M614 18c-14-14-36-7-39 10-20-6-38 9-33 25-21 5-28 28-13 40-13 24 8 42 28 36 10 21 35 24 50 10 18 12 40 6 46-11 22-2 32-23 20-40 11-19 1-39-18-43-5-22-23-31-41-27Z" fill="#a4bf9f" stroke="#698668" stroke-width="1.8"/><path d="M546 86c12 8 21 6 29-1 10 13 25 14 36 5 12 10 28 8 35-1m-75-30 7-2m35-12 7 2m-36 60 8 2m30 3 6-2" stroke="#80a17b" stroke-width="1.8" stroke-linecap="round"/></g>
                  <g class="garden-plant" transform="translate(77 209)"><path d="M0 28 3-27M1 12-17-4m19 0 19-23" stroke="#6b8d65" stroke-width="2"/><path d="M0 11c-28-1-35-19-27-29 18 3 27 16 27 29Zm2-12c3-22 19-35 31-31 1 22-10 33-31 31Zm1-14C-12-26-13-42-3-50 11-38 12-26 3-15Z" fill="#9db78d" stroke="#739369" stroke-width="1.5"/></g>
                  <ellipse cx="339" cy="255" rx="175" ry="20" fill="#aabc9a" opacity=".4"/>
                  <g class="reader-breathe">
                    <path d="m210 202-12 42m42-41 13 42" stroke="#7f9174" stroke-width="7" stroke-linecap="round"/>
                    <path d="M182 182c-9-20-14-58 0-64 15-7 37 24 40 42l10 27Z" fill="#c1cba9" stroke="#6c8566" stroke-width="1.8"/>
                    <path d="M208 156c12-11 40-7 47 10l10 37-55 8-20-15Z" fill="#829eac" stroke="#5b786f" stroke-width="1.8"/>
                    <path d="m215 202-4 37 17 4 13-41m-29 37-8 7c-4 6 13 9 23 2l1-5m13-5 9 8c8 5 22-1 14-7l-12-5" fill="#faf4df" stroke="#667d63" stroke-width="1.8" stroke-linejoin="round"/>
                    <path d="M203 105c-19-27-18-59-6-60 14-1 19 34 19 54m13-1c-2-34 10-60 20-54 12 8-2 39-9 59" fill="#fbf5e3" stroke="#667e65" stroke-width="1.8"/>
                    <path d="M204 88c-10-23-11-32-8-32 5-1 10 18 12 31m26 0c2-18 8-32 10-30 4 2-2 19-6 30" stroke="#d1b6a0" stroke-width="3" stroke-linecap="round"/>
                    <path d="M187 126c-2-31 53-46 69-15 10 20 2 42-18 48-21 7-49-8-51-33Z" fill="#fbf5e3" stroke="#667e65" stroke-width="1.8"/>
                    <g class="reader-eyes" fill="#4f6853"><ellipse cx="215" cy="124" rx="2.4" ry="3.3"/><ellipse cx="239" cy="122" rx="2.4" ry="3.3"/></g>
                    <path d="m225 133 3 2 3-3m-3 3v4m0 0c-4 4-7 3-9 1m9-1c4 3 7 2 8-1" stroke="#7b8163" stroke-width="1.3" stroke-linecap="round"/>
                    <ellipse cx="203" cy="136" rx="6" ry="3" fill="#ddbd9e" opacity=".6"/>
                    <path d="M248 165c14 2 21 9 33 10l5 10c-17 6-37 0-45-8" fill="#fbf5e3" stroke="#667e65" stroke-width="1.8" stroke-linecap="round"/>
                  </g>
                  <g class="room-friend">
                    <path d="m427 202-9 39m45-39 9 39" stroke="#7f9174" stroke-width="7" stroke-linecap="round"/>
                    <path d="M475 188c20-21 28-48 16-58-16-12-38 12-46 29l-6 29Z" fill="#c1cba9" stroke="#6c8566" stroke-width="1.8"/>
                    <path d="M422 160c13-9 36-2 44 14l10 28-59 9-12-28Z" fill="#b1b78c" stroke="#6d8161" stroke-width="1.8"/>
                    <path d="m424 203-14 31c-21-1-22 9-5 12l18-4 17-34m16-2 4 32c16-1 23 9 5 11l-15-4-12-36" fill="#e0c8a1" stroke="#7c8362" stroke-width="1.8" stroke-linejoin="round"/>
                    <circle cx="410" cy="102" r="15" fill="#dfc7a1" stroke="#788260" stroke-width="1.8"/><circle cx="458" cy="100" r="15" fill="#dfc7a1" stroke="#788260" stroke-width="1.8"/>
                    <circle cx="409" cy="103" r="7" fill="#c3a983"/><circle cx="458" cy="101" r="7" fill="#c3a983"/>
                    <path d="M403 126c-4-29 47-44 65-16 14 22 1 45-21 49-23 4-42-11-44-33Z" fill="#e2cca9" stroke="#788260" stroke-width="1.8"/>
                    <ellipse cx="433" cy="139" rx="17" ry="12" fill="#f2e5c9"/>
                    <g fill="#4f6853"><circle cx="419" cy="125" r="2.2"/><circle cx="445" cy="123" r="2.2"/></g>
                    <g stroke="#5e795f" stroke-width="1.5"><circle cx="419" cy="125" r="9"/><circle cx="445" cy="123" r="9"/><path d="m428 124 8-1m-26 0-9-2"/></g>
                    <path d="m429 136 4 3 4-4m-4 4v4m0 0c-4 4-7 2-8 1m8-1c3 4 7 3 9 1" stroke="#7e8160" stroke-width="1.3" stroke-linecap="round"/>
                    <path d="M415 168c-14 4-24 12-38 10l-2 10c19 8 33 0 43-7" fill="#e2cca9" stroke="#788260" stroke-width="1.8"/>
                  </g>
                  <path d="m285 188-9 65m110-65 9 65" stroke="#8c9775" stroke-width="8" stroke-linecap="round"/>
                  <path d="m286 214 101 0" stroke="#a2aa87" stroke-width="5"/>
                  <ellipse cx="334" cy="183" rx="97" ry="25" fill="#a7af87" stroke="#6f8564" stroke-width="1.8"/>
                  <ellipse cx="334" cy="177" rx="97" ry="25" fill="#e2dfbb" stroke="#6f8564" stroke-width="1.8"/>
                  <path d="m292 174 31-6 23 7 27-2-8 17-26 4-22-9-31 3Z" fill="#fff9e5" stroke="#889577" stroke-width="1.3" stroke-linejoin="round"/><path class="book-page" d="m340 175 26-3-8 14-23 5Z" fill="#f6f0d8" stroke="#a6b08c"/><path d="m324 169 12 22m-35-14 17-3m-19 8 17-3" stroke="#bec3a4" stroke-width="1.2"/>
                  <g transform="translate(383 154)"><path d="M0 0h13v16c-4 3-10 3-13 0Z" fill="#fcf7e5" stroke="#7d9471" stroke-width="1.4"/><path d="M13 3c12-2 12 12 0 10" stroke="#7d9471" stroke-width="1.4"/><g class="garden-steam" stroke="#9faf8d" stroke-width="1.2" stroke-linecap="round"><path d="M4-4c-5-5 4-7 0-13m6 13c-4-4 3-6 1-10"/></g></g>
                  <path d="m268 166 10-16" stroke="#68899a" stroke-width="3" stroke-linecap="round"/><path d="m267 168 1-3" stroke="#7d8062" stroke-width="2" stroke-linecap="round"/>
                  <g transform="translate(118 244) rotate(-7)"><path d="M0 0h43v9H0Z" fill="#8fa9b2" stroke="#71836a" stroke-width="1.3"/><path d="M4-9h36v9H4Z" fill="#e3d5a5" stroke="#7e896b" stroke-width="1.3"/><path d="M7-5h30M4 5h35" stroke="#f5efdb" stroke-width="2"/></g>
                  <g stroke="#7e9a6f" stroke-width="1.5" stroke-linecap="round"><path d="m61 260-2-10m2 10 7-6m-7 6-7-3m472-7-2-10m2 10 7-6m-7 6-7-3M566 222l-2-9m2 9 7-5"/></g>
                  <g class="room-speech"><path d="M282 54c0-10 9-17 20-17h33c13 0 21 7 21 17s-8 18-21 18h-20l-13 9 2-9h-2c-11 0-20-7-20-18Z" fill="#fafbef" stroke="#a6b99a" stroke-width="1.3"/><path d="m306 53 8 8 17-17" stroke="#84a075" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></g>
                </svg>
                </div>
                <button class="motion-toggle" type="button" data-motion-toggle aria-pressed="false" aria-label="Jeda animasi"><span data-icon="pause"></span></button>
            </section>

            <section class="room-join" aria-labelledby="room-join-title">
                <span class="room-invite-icon" data-icon="mail"></span>
                <p class="eyebrow">Sudah diajak teman?</p>
                <h2 id="room-join-title">Masuk pakai kode</h2>
                <p class="room-join-description">Minta PIN ruangnya, lalu tulis di bawah.</p>
                <form method="POST" action="{{ route('study-rooms.join') }}" data-room-join>
                    @csrf
                    <label for="room-pin">PIN ruang</label>
                    <input id="room-pin" name="pin" class="room-input" value="{{ old('pin') }}" placeholder="1234" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="off" spellcheck="false" required aria-describedby="room-pin-hint{{ $errors->has('pin') ? ' room-pin-error' : '' }}" aria-invalid="{{ $errors->has('pin') ? 'true' : 'false' }}">
                    <span id="room-pin-hint" class="room-code-hint">4 angka dari temanmu.</span>
                    @error('pin')<p class="room-code-error" id="room-pin-error" role="alert">{{ $message }}</p>@enderror
                    <button class="button button-primary" type="submit">Gabung ruang<span data-icon="arrow-right"></span></button>
                </form>
                <div class="room-join-footer"><span data-icon="users"></span><p>Belum punya PIN?<br><a class="text-link" href="#room-materials">Bikin ruang sendiri</a></p></div>
            </section>
        </div>

        @if($activeRooms->total() > 0)
            <section class="rooms-active" data-pagination-region="rooms_page" aria-labelledby="rooms-active-title">
                <div class="section-heading"><div><h2 id="rooms-active-title">Obrolannya masih lanjut</h2><p>Ruang aktif yang kamu buat atau ikuti.</p></div></div>
                <div class="rooms-active-list">
                    @foreach($activeRooms as $room)
                        <a class="rooms-active-item" href="{{ route('study-rooms.show', $room) }}">
                            <span class="rooms-active-symbol" data-icon="users"></span>
                            <span class="rooms-active-copy"><strong>{{ $room->target ? ($room->target->name ?? $room->target->title) : 'Materi sudah dihapus' }}</strong><span>{{ $room->host_id === auth()->id() ? 'Ruangmu' : 'Dibuat '.$room->host->name }} <span aria-hidden="true">/</span> PIN {{ $room->pin }} <span aria-hidden="true">/</span> {{ $room->users->count() + 1 }} anggota</span></span>
                            <span class="rooms-active-link">Lanjut<span data-icon="arrow-right"></span></span>
                        </a>
                    @endforeach
                </div>
                {{ $activeRooms->links() }}
            </section>
        @endif

        <section class="room-materials" data-pagination-region="materials_page" id="room-materials" aria-labelledby="room-materials-title">
            <div class="section-heading"><div><h2 id="room-materials-title">Atau, kamu yang ajak duluan</h2><p>Pilih bahan obrolannya. PIN ruang menyusul.</p></div><button type="button" class="text-link" data-room-upload-open>Upload materi baru<span data-icon="upload"></span></button></div>
            @if($myActiveRooms->isNotEmpty())
                <p class="rooms-create-note"><span data-icon="info"></span>Membuat ruang baru akan menutup ruang yang sedang kamu kelola.</p>
            @endif
            @if($materials->total() === 0)
                <div class="rooms-empty"><span data-icon="book-open"></span><h3>Bahan obrolannya belum ada</h3><p>Upload catatan atau materi kuliahmu, lalu ajak teman belajar.</p><button type="button" class="button button-primary" data-room-upload-open>Upload materi<span data-icon="arrow-right"></span></button></div>
            @else
                <div class="room-material-list">
                    @foreach($materials as $material)
                        <form method="POST" action="{{ route('study-rooms.store') }}" data-room-create>
                            @csrf
                            <input type="hidden" name="source_type" value="{{ $material['type'] }}">
                            <input type="hidden" name="{{ $material['type'] === 'folder' ? 'folder_id' : 'document_id' }}" value="{{ $material['id'] }}">
                            <button type="submit" class="room-material" aria-label="Bikin ruang untuk {{ $material['title'] }}"><span class="room-book {{ $material['type'] === 'folder' ? 'room-book-folder' : '' }}" aria-hidden="true"><span>{{ $material['type'] === 'folder' ? 'Folder' : 'File' }}</span><span data-icon="{{ $material['type'] === 'folder' ? 'folder' : 'file' }}"></span><span>Materi bersama</span></span><span class="room-material-copy"><small>{{ $material['meta'] }}</small><strong>{{ $material['title'] }}</strong><span>Bikin ruang<span data-icon="arrow-up-right"></span></span></span></button>
                        </form>
                    @endforeach
                </div>
                {{ $materials->links() }}
            @endif
        </section>

        <dialog id="room-upload-dialog" class="room-upload-dialog" aria-labelledby="room-upload-title" data-reopen="{{ old('source_type') === 'upload' ? 'true' : 'false' }}">
            <div class="dialog-heading"><div><p class="eyebrow">Kamu yang mulai</p><h2 id="room-upload-title">Bawa bahan obrolannya</h2></div><button type="button" class="icon-button" data-room-upload-close aria-label="Tutup upload"><span data-icon="close"></span></button></div>
            <p>Upload materi, lalu bagikan PIN ruangnya ke teman.</p>
            <form method="POST" action="{{ route('study-rooms.store') }}" enctype="multipart/form-data" data-room-upload>
                @csrf
                <input type="hidden" name="source_type" value="upload">
                <label class="room-upload-zone" for="room-files"><span data-icon="upload"></span><strong>Pilih file atau seret ke sini</strong><span>PDF, Word, PowerPoint, atau gambar. Maks. 20 MB per file.</span><input id="room-files" type="file" name="files[]" accept=".pdf,.docx,.pptx,.jpg,.jpeg,.png,.gif,.webp" multiple required></label>
                <ul class="room-upload-files" data-room-files aria-live="polite"></ul>
                <label for="room-folder">Simpan ke folder</label>
                <select id="room-folder" name="folder_id"><option value="">Tanpa folder</option><option value="new" @selected(old('folder_id') === 'new')>Buat folder baru</option>@foreach($allFolders as $folder)<option value="{{ $folder->id }}" @selected((string) old('folder_id') === (string) $folder->id)>{{ $folder->name }}</option>@endforeach</select>
                <div class="room-folder-name" data-room-folder-name @if(old('folder_id') !== 'new') hidden @endif><label for="room-new-folder">Nama folder baru</label><input id="room-new-folder" type="text" name="new_folder_name" value="{{ old('new_folder_name') }}" maxlength="250" placeholder="Contoh: Catatan biologi" @required(old('folder_id') === 'new')></div>
                @if($myActiveRooms->isNotEmpty())<p class="rooms-create-note">Ruang yang sedang kamu kelola akan ditutup saat ruang baru dibuat.</p>@endif
                <p class="room-upload-error" data-room-upload-error role="alert" hidden></p>
                <div class="room-upload-progress" data-room-upload-progress hidden><progress max="100" value="0" aria-label="Progres upload materi"></progress><p role="status">Mengunggah materi...</p></div>
                <button class="button button-primary" type="submit">Upload &amp; bikin ruang<span data-icon="arrow-right"></span></button>
            </form>
        </dialog>
    </div>
</x-app-layout>
