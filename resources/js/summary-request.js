export function summaryRequest() {
    return {
        loading: false, error: '', statusUrl: null, submissionKey: null, controller: null, disposed: false,
        destroy() { this.disposed = true; this.controller?.abort(); },
        async request(url, options = {}) {
            this.controller = new AbortController();
            const timer = setTimeout(() => this.controller?.abort(), options.method === 'POST' ? 370000 : 15000);
            try {
                const response = await fetch(url, {
                    ...options, signal: this.controller.signal, cache: 'no-store',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (response.redirected || [401, 419].includes(response.status)) {
                    throw new Error('Sesi berakhir, masuk kembali untuk membuka ringkasan');
                }
                if (!response.ok) throw new Error('Ringkasan belum bisa dicek, coba lagi');
                const state = await response.json();
                if (!['queued', 'processing', 'completed', 'failed'].includes(state.status)) {
                    throw new Error('Status ringkasan belum terkonfirmasi, coba lagi');
                }
                return state;
            } finally {
                clearTimeout(timer);
                this.controller = null;
            }
        },
        async waitForResult(state) {
            while (!this.disposed) {
                if (['completed', 'failed'].includes(state.status)) {
                    window.location.assign(state.url);
                    return;
                }
                this.statusUrl = state.status_url;
                await new Promise(resolve => setTimeout(resolve, 2000));
                if (this.disposed) return;
                state = await this.request(this.statusUrl);
            }
        },
        async submit(form) {
            if (this.loading) return;
            this.loading = true;
            this.error = '';
            this.setBusy?.(true);
            try {
                const body = new FormData(form);
                const submissionKey = JSON.stringify([...body.entries()]);
                if (this.submissionKey !== submissionKey) this.statusUrl = null;
                this.submissionKey = submissionKey;
                const state = this.statusUrl
                    ? await this.request(this.statusUrl)
                    : await this.request(form.action, { method: 'POST', body });
                await this.waitForResult(state);
            } catch (error) {
                if (!this.disposed) this.error = error.name === 'AbortError'
                    ? 'Status belum terkonfirmasi. Klik lagi untuk mengecek proses yang sama'
                    : (error.message || 'Koneksi terputus, coba cek ringkasan lagi');
                this.loading = false;
                this.setBusy?.(false);
            }
        },
        async watch(statusUrl) {
            if (this.loading) return;
            this.loading = true;
            this.error = '';
            try {
                await this.waitForResult(await this.request(statusUrl));
            } catch (error) {
                if (!this.disposed) this.error = 'Status belum bisa dicek. Tekan Cek lagi';
                this.loading = false;
            }
        },
    };
}
