<dialog x-ref="materialDialog" class="chat-material-dialog" aria-labelledby="chat-material-title"
        :aria-busy="materialUploading" @cancel="if (materialUploading) $event.preventDefault()"
        @click="if ($event.target === $el) { const box = $el.getBoundingClientRect(); if ($event.clientX < box.left || $event.clientX > box.right || $event.clientY < box.top || $event.clientY > box.bottom) closeMaterials(); }">
    <div class="chat-material-heading">
        <div><h2 id="chat-material-title">Tambah materi</h2><p>Pilih koleksi atau upload file untuk dibahas</p></div>
        <button type="button" class="chat-material-close" :disabled="materialUploading" @click="closeMaterials()" aria-label="Tutup pilihan materi"><i data-lucide="x" aria-hidden="true"></i></button>
    </div>
    <input type="file" x-ref="materialInput" @change="uploadMaterial($event)" accept=".pdf,.docx,.pptx" hidden>
    <button type="button" class="chat-material-upload" :disabled="materialUploading || materials.length >= 10" @click="$refs.materialInput.click()">
        <i data-lucide="upload" aria-hidden="true"></i>
        <span x-text="materialUploading ? 'Mengunggah dan membaca file...' : 'Upload file baru'"></span>
    </button>
    <p class="chat-material-help">PDF, DOCX, PPTX hingga 20 MB. File tersimpan di Materi saya</p>
    <label for="chat-material-search" class="chat-material-label">Koleksi materi</label>
    <input id="chat-material-search" type="search" x-model="materialSearch" @input.debounce.250ms="loadMaterials(1)" @keydown.enter.prevent="loadMaterials(1)" maxlength="200" placeholder="Cari judul atau nama file" autofocus>
    <p role="alert" x-show="materialError" x-cloak x-text="materialError" class="chat-material-error"></p>
    <p role="status" x-show="materialNotice" x-cloak x-text="materialNotice" class="chat-material-help"></p>
    <div class="chat-material-list" :aria-busy="materialLoading">
        <p x-show="materialLoading" class="chat-material-help">Memuat koleksi...</p>
        <p x-show="materialListReady && !materialLoading && !materialItems.length" x-cloak class="chat-material-help">Belum ada materi yang cocok dan siap dibaca. Coba pencarian lain atau upload file</p>
        <template x-for="item in materialItems" :key="item.id">
            <button type="button" class="chat-material-option" :aria-pressed="hasMaterial(item.id)" :disabled="materialLoading || materialUploading" @click="toggleMaterial(item)">
                <i data-lucide="file-text" aria-hidden="true"></i>
                <span><strong x-text="item.title"></strong><small x-text="item.name || item.extension"></small></span>
                <span class="chat-material-check" x-text="hasMaterial(item.id) ? 'Dipilih' : item.extension"></span>
            </button>
        </template>
    </div>
    <div class="chat-material-pagination">
        <button type="button" :disabled="materialLoading || materialPage <= 1" @click="loadMaterials(materialPage - 1)">Sebelumnya</button>
        <span x-text="`${materialPage} / ${materialPages}`" aria-live="polite"></span>
        <button type="button" :disabled="materialLoading || materialPage >= materialPages" @click="loadMaterials(materialPage + 1)">Selanjutnya</button>
    </div>
    <div class="chat-material-footer"><span x-text="`${materials.length} materi dipilih`"></span><button type="button" class="button button-primary" :disabled="materialUploading" @click="closeMaterials()">Selesai</button></div>
</dialog>
