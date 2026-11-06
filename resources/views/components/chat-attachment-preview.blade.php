<dialog x-ref="attachmentPreview" class="chat-attachment-preview" aria-labelledby="attachment-preview-title"
    @close="if (!$el.open) clearAttachmentPreview()"
    @click="if ($event.target === $el) { const box = $el.getBoundingClientRect(); if ($event.clientX < box.left || $event.clientX > box.right || $event.clientY < box.top || $event.clientY > box.bottom) closeAttachmentPreview(); }">
    <header class="chat-attachment-heading">
        <h2 id="attachment-preview-title" x-text="attachmentPreview.title"></h2>
        <button type="button" class="chat-material-close" @click="closeAttachmentPreview()" aria-label="Tutup preview" autofocus>&times;</button>
    </header>
    <div class="chat-attachment-body" :aria-busy="attachmentPreview.loading">
        <p x-show="attachmentPreview.loading" role="status">Memuat file...</p>
        <p x-show="attachmentPreview.error" x-text="attachmentPreview.error" role="alert"></p>
        <template x-if="attachmentPreview.kind === 'image'">
            <img class="chat-attachment-full-image" :src="attachmentPreview.url" alt="Gambar yang dikirim" x-on:error="attachmentPreview.error = 'Gambar tidak tersedia atau gagal dimuat'">
        </template>
        <template x-if="attachmentPreview.kind === 'pdf'">
            <div class="chat-attachment-pdf">
                <iframe :src="attachmentPreview.url" title="Isi PDF"></iframe>
                <button type="button" class="text-link" @click="attachmentPreview.kind = 'text'">Tampilkan versi teks</button>
            </div>
        </template>
        <template x-if="attachmentPreview.kind === 'text'">
            <div><p class="chat-attachment-note">Isi teks dokumen</p><pre class="chat-attachment-text" x-text="attachmentPreview.text || 'Teks file belum tersedia'"></pre></div>
        </template>
    </div>
</dialog>
