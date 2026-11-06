export function chatMaterials(options) {
    let searchRequest = 0;
    return {
        materials: options.materials,
        materialItems: [], materialSearch: '', materialPage: 1, materialPages: 1,
        materialLoading: false, materialUploading: false, materialError: '',
        materialNotice: '', materialListReady: false,
        refreshMaterialIcons() {
            this.$nextTick(() => window.lucide?.createIcons());
        },
        async openMaterials() {
            if (this.sending) return;
            this.materialError = '';
            this.materialNotice = '';
            this.$refs.materialDialog.showModal();
            await this.loadMaterials(1);
        },
        closeMaterials() {
            if (!this.materialUploading) this.$refs.materialDialog.close();
        },
        async loadMaterials(page = 1) {
            const request = ++searchRequest;
            this.materialLoading = true;
            this.materialError = '';
            try {
                const url = new URL(options.listUrl, location.href);
                url.searchParams.set('q', this.materialSearch);
                url.searchParams.set('page', page);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok || response.redirected) throw new Error('Koleksi belum bisa dimuat, coba lagi');
                const data = await response.json();
                if (request !== searchRequest) return;
                this.materialItems = data.items;
                this.materialPage = data.page;
                this.materialPages = data.pages;
                this.materialListReady = true;
                this.refreshMaterialIcons();
            } catch (error) {
                if (request === searchRequest) this.materialError = error.message;
            } finally {
                if (request === searchRequest) this.materialLoading = false;
            }
        },
        hasMaterial(id) { return this.materials.some(item => item.id === id); },
        toggleMaterial(item) {
            if (this.sending || this.materialUploading) return;
            this.materialError = '';
            if (this.hasMaterial(item.id)) {
                this.materials = this.materials.filter(material => material.id !== item.id);
            } else if (this.materials.length < 10) {
                this.materials.push({ id: item.id, title: item.title, extension: item.extension });
            } else {
                this.materialError = 'Maksimal 10 materi dalam percakapan';
            }
            this.refreshMaterialIcons();
        },
        async uploadMaterial(event) {
            const file = event.target.files[0];
            event.target.value = '';
            if (!file || this.materialUploading || this.sending) return;
            this.materialError = '';
            this.materialNotice = '';
            if (this.materials.length >= 10) {
                this.materialError = 'Lepas satu materi sebelum mengunggah file baru';
                return;
            }
            if (!/\.(pdf|docx|pptx)$/i.test(file.name) || file.size > 20 * 1024 * 1024) {
                this.materialError = 'Pilih PDF, DOCX, atau PPTX maksimal 20 MB';
                return;
            }
            this.materialUploading = true;
            try {
                const body = new FormData();
                body.append('file', file);
                const response = await fetch(options.uploadUrl, {
                    method: 'POST', body,
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': options.csrf },
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || response.redirected) {
                    throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Upload gagal, coba lagi');
                }
                this.materials.push(data.material);
                this.refreshMaterialIcons();
                this.materialNotice = 'Materi ditambahkan dan tersimpan di Materi saya';
                await this.loadMaterials(1);
            } catch (error) {
                this.materialError = error.message || 'Upload gagal, coba lagi';
            } finally {
                this.materialUploading = false;
            }
        },
    };
}
