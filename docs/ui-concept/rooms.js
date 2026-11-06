const roomMaterials = {
  biologi: 'Sistem Pernapasan Manusia',
  ekonomi: 'Pengantar Ekonomi Mikro',
  statistika: 'Statistika Dasar',
};
const createRoomDialog = document.querySelector('#create-room-dialog');
const roomPreviewDialog = document.querySelector('#room-preview-dialog');
const roomCode = document.querySelector('#room-code');
const roomCodeError = document.querySelector('#room-code-error');

function showRoomPreview(material, code, joining = false) {
  document.querySelector('#room-preview-title').textContent = joining ? 'Begini contoh ruangnya.' : 'Tempat ngobrolnya siap.';
  document.querySelector('#room-preview-material').textContent = roomMaterials[material];
  document.querySelector('.room-preview-material div>span').textContent = joining ? 'Materi contoh untuk preview' : 'Bahan yang dibahas';
  document.querySelector('#room-preview-code').textContent = code;
  document.querySelector('#room-preview-code-label').textContent = joining ? 'Kode yang kamu isi' : 'Kode contoh';
  document.querySelector('.room-preview-code>p').textContent = joining ? 'Preview ini tidak mencari atau masuk ke ruang sungguhan.' : 'Di aplikasi nanti, bagikan kode ini ke temanmu.';
  document.querySelector('#room-preview-link').href = `#study?material=${material}&tab=chat`;
  roomPreviewDialog.showModal();
}

document.querySelectorAll('[data-create-room]').forEach(button => button.addEventListener('click', () => {
  const selected = Object.hasOwn(roomMaterials, button.dataset.createRoom) ? button.dataset.createRoom : 'biologi';
  document.querySelector(`#create-room-form input[value="${selected}"]`).checked = true;
  createRoomDialog.showModal();
}));

document.querySelector('#create-room-form').addEventListener('submit', event => {
  event.preventDefault();
  const material = new FormData(event.currentTarget).get('material');
  if (!Object.hasOwn(roomMaterials, material)) return;
  createRoomDialog.close();
  showRoomPreview(material, '482913');
});

document.querySelector('#join-form').addEventListener('submit', event => {
  event.preventDefault();
  const code = roomCode.value.trim();
  const valid = /^[0-9]{6}$/.test(code);
  roomCodeError.hidden = valid;
  roomCodeError.textContent = valid ? '' : 'Isi 6 angka kode ruangnya dulu, ya.';
  roomCode.setAttribute('aria-invalid', String(!valid));
  if (!valid) { roomCode.focus(); return; }
  showRoomPreview('biologi', code, true);
});

roomCode.addEventListener('input', () => {
  if (/^[0-9]{6}$/.test(roomCode.value)) {
    roomCodeError.hidden = true;
    roomCode.setAttribute('aria-invalid', 'false');
  }
});

// Accept commonly formatted codes without splitting keyboard focus across six inputs.
roomCode.addEventListener('paste', event => {
  const code = event.clipboardData?.getData('text').replace(/[\s-]/g, '');
  if (code && /^[0-9]{6}$/.test(code)) {
    event.preventDefault();
    roomCode.value = code;
    roomCode.dispatchEvent(new Event('input', { bubbles: true }));
  }
});
document.querySelector('#room-preview-link').addEventListener('click', () => roomPreviewDialog.close());
window.addEventListener('hashchange', () => {
  if (location.hash.split('?')[0] !== '#rooms') {
    if (createRoomDialog.open) createRoomDialog.close();
    if (roomPreviewDialog.open) roomPreviewDialog.close();
  }
});
