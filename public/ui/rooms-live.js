(() => {
    const page = document.querySelector('.rooms-live');
    if (!page) return;

    page.addEventListener('submit', event => {
        if (event.target.matches('[data-room-create], [data-room-join]')) {
            event.target.querySelector('button[type="submit"]').disabled = true;
        }
    });

    const dialog = page.querySelector('#room-upload-dialog');
    const form = dialog.querySelector('[data-room-upload]');
    const filesInput = form.querySelector('#room-files');
    const filesList = form.querySelector('[data-room-files]');
    const folderSelect = form.querySelector('#room-folder');
    const folderName = form.querySelector('[data-room-folder-name]');
    const error = form.querySelector('[data-room-upload-error]');
    const progressBox = form.querySelector('[data-room-upload-progress]');
    const progress = progressBox.querySelector('progress');
    const status = progressBox.querySelector('[role="status"]');
    const submit = form.querySelector('[type="submit"]');
    const close = dialog.querySelector('[data-room-upload-close]');
    let uploading = false;

    page.addEventListener('click', event => {
        if (event.target.closest('[data-room-upload-open]')) dialog.showModal();
    });
    close.addEventListener('click', () => dialog.close());
    dialog.addEventListener('cancel', event => {
        if (uploading) event.preventDefault();
    });
    if (dialog.dataset.reopen === 'true') dialog.showModal();

    folderSelect.addEventListener('change', () => {
        const creatingFolder = folderSelect.value === 'new';
        folderName.hidden = !creatingFolder;
        folderName.querySelector('input').required = creatingFolder;
    });

    filesInput.addEventListener('change', () => {
        filesList.replaceChildren();
        for (const file of filesInput.files) {
            const item = document.createElement('li');
            item.textContent = `${file.name} / ${(file.size / 1024 / 1024).toFixed(1)} MB`;
            filesList.append(item);
        }
        const oversized = Array.from(filesInput.files).some(file => file.size > 20 * 1024 * 1024);
        filesInput.setCustomValidity(oversized ? 'Ukuran tiap file maksimal 20 MB.' : '');
        if (oversized) filesInput.reportValidity();
    });

    function fail(message) {
        uploading = false;
        submit.disabled = false;
        close.disabled = false;
        form.removeAttribute('aria-busy');
        progressBox.hidden = true;
        error.textContent = message;
        error.hidden = false;
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        if (uploading || !form.reportValidity()) return;
        uploading = true;
        submit.disabled = true;
        close.disabled = true;
        form.setAttribute('aria-busy', 'true');
        error.hidden = true;
        progressBox.hidden = false;
        progress.value = 0;
        status.textContent = 'Mengunggah materi...';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.addEventListener('progress', event => {
            if (!event.lengthComputable) return;
            progress.value = Math.round(event.loaded / event.total * 100);
            status.textContent = progress.value < 100
                ? `Mengunggah materi... ${progress.value}%`
                : 'Materi terkirim. Menyiapkan ruang belajarmu...';
        });
        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 300 && xhr.responseURL) {
                window.location.assign(xhr.responseURL);
                return;
            }
            let message = xhr.status === 419
                ? 'Sesi sudah habis. Muat ulang halaman, lalu coba lagi.'
                : 'Materi belum berhasil diunggah. Coba lagi.';
            try {
                const response = JSON.parse(xhr.responseText);
                message = Object.values(response.errors || {}).flat()[0] || response.message || message;
            } catch (_) {
                if (xhr.status === 413) message = 'Ukuran upload terlalu besar. Coba kurangi jumlah file.';
            }
            fail(message);
        });
        xhr.addEventListener('error', () => fail('Koneksi terputus. Periksa jaringanmu, lalu coba lagi.'));
        xhr.addEventListener('abort', () => fail('Upload dibatalkan. File bisa dipilih dan diunggah lagi.'));
        xhr.send(new FormData(form));
    });
})();
