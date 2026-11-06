export function chatAttachmentPreview(options) {
    let request;
    return {
        attachmentPreview: { title: '', kind: '', url: '', text: '', loading: false, error: '' },
        imageUrl(path) {
            if (!path) return '';
            if (path.startsWith('data:image/')) return path;
            try {
                const url = new URL(path, location.origin + '/');
                return url.origin === location.origin ? url.href : '';
            } catch { return ''; }
        },
        showAttachment() {
            if (!this.$refs.attachmentPreview.open) this.$refs.attachmentPreview.showModal();
        },
        openImagePreview(path) {
            request?.abort();
            this.attachmentPreview = { title: 'Gambar', kind: 'image', url: this.imageUrl(path), text: '', loading: false, error: '' };
            this.showAttachment();
        },
        async openFilePreview(material) {
            request?.abort();
            const current = new AbortController();
            request = current;
            this.attachmentPreview = { title: material.title, kind: '', url: '', text: '', loading: true, error: '' };
            this.showAttachment();
            try {
                const url = options.documentUrl.replace('__DOCUMENT__', encodeURIComponent(material.id));
                const response = await fetch(url, { signal: current.signal, headers: { Accept: 'application/json' } });
                if (!response.ok || response.redirected) throw new Error('File tidak tersedia atau tidak bisa dibuka');
                const data = await response.json();
                if (request !== current) return;
                this.attachmentPreview = { title: data.title, kind: data.kind, url: this.imageUrl(data.url), text: data.text, loading: false, error: '' };
            } catch (error) {
                if (current.signal.aborted || request !== current) return;
                this.attachmentPreview.error = 'File tidak tersedia atau tidak bisa dibuka';
                this.attachmentPreview.loading = false;
            }
        },
        closeAttachmentPreview() {
            this.$refs.attachmentPreview.close();
            this.clearAttachmentPreview();
        },
        clearAttachmentPreview() {
            request?.abort();
            request = null;
            this.attachmentPreview = { title: '', kind: '', url: '', text: '', loading: false, error: '' };
        },
    };
}
