export function flashcardDeck(cards) {
    return {
        cards, current: 0, open: false, saving: false, error: '', notice: '', listPage: 0,
        labels: { baru: 'Baru', sulit: 'Sulit', ulang: 'Ulang lagi', paham: 'Paham' },
        get total() { return this.cards.length; },
        count(status) { return this.cards.filter(card => card.study_status === status).length; },
        go(index) {
            if (this.saving || index < 0 || index >= this.total) return;
            this.current = index;
            this.listPage = Math.floor(index / 5);
            this.open = false;
            this.error = '';
            this.notice = '';
        },
        async review(form) {
            if (this.saving) return;
            this.saving = true;
            this.error = '';
            this.notice = '';
            const index = this.current;
            try {
                const response = await fetch(form.action, {
                    method: 'POST', body: new FormData(form),
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok || response.redirected) {
                    throw new Error(response.status === 419 || response.status === 401 || response.redirected
                        ? 'Sesi berakhir, masuk kembali sebelum menyimpan kartu'
                        : 'Status belum tersimpan, coba lagi');
                }
                const saved = await response.json();
                if (saved.id !== this.cards[index].id || !this.labels[saved.study_status]) {
                    throw new Error('Status belum terkonfirmasi, coba lagi');
                }
                Object.assign(this.cards[index], saved);
                this.open = false;
                if (index < this.total - 1) {
                    this.current = index + 1;
                    this.listPage = Math.floor(this.current / 5);
                    this.notice = 'Tersimpan, lanjut ke kartu berikutnya';
                } else {
                    this.notice = 'Tersimpan, kamu sudah sampai kartu terakhir';
                }
            } catch (error) {
                this.error = error.message || 'Status belum tersimpan, coba lagi';
            } finally {
                this.saving = false;
            }
        },
    };
}
