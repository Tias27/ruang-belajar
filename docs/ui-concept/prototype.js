const iconPaths = {
  pause: '<path d="M8 5v14M16 5v14"/>',
  play: '<path d="m8 4 12 8-12 8Z"/>',
  'arrow-down': '<path d="M12 4v16m-6-6 6 6 6-6"/>',
  eye: '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
  'eye-off': '<path d="m3 3 18 18M10.6 5.1 12 5c6 0 10 7 10 7a20 20 0 0 1-3 3.8M6 6.5A23 23 0 0 0 2 12s4 7 10 7a10 10 0 0 0 5-1.3M10 10a3 3 0 0 0 4 4"/>',
  mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>',
  info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>',
  hand: '<path d="M8 12V5a2 2 0 0 1 4 0v6-2a2 2 0 0 1 4 0v3-1a2 2 0 0 1 4 0v5c0 4-3 6-6 6h-1c-2 0-4-1-5-3l-4-6a2 2 0 0 1 3-2l1 1Z"/>',
  book: '<path d="M3 5c3-1 6 0 9 2 3-2 6-3 9-2v15c-3-1-6 0-9 2-3-2-6-3-9-2Z"/><path d="M12 7v15M6 9l3 1M15 10l3-1M6 13l3 1M15 14l3-1"/>',
  'book-open': '<path d="M12 5v16M3 4c4-1 6 0 9 2 3-2 5-3 9-2v15c-4-1-6 0-9 2-3-2-5-3-9-2Z"/>',
  home: '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/>',
  folder: '<path d="M3 7V5a2 2 0 0 1 2-2h4l3 3h7a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path d="M3 9h18"/>',
  history: '<path d="M3 11a9 9 0 1 1 2 7M3 4v7h7M12 7v5l3 2"/>',
  users: '<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M17 4a3 3 0 0 1 0 6M17 13a5 5 0 0 1 4 5v3"/>',
  help: '<circle cx="12" cy="12" r="9"/><path d="M9 9a3 3 0 0 1 6 0c0 2-3 2-3 4M12 17h.01"/>',
  'arrow-up-right': '<path d="M6 18 18 6M6 6h12v12"/>',
  'arrow-right': '<path d="M4 12h16m-6-6 6 6-6 6"/>',
  'arrow-left': '<path d="M20 12H4m6-6-6 6 6 6"/>',
  'arrow-up': '<path d="M12 20V4m-6 6 6-6 6 6"/>',
  'chevron-right': '<path d="m9 5 7 7-7 7"/>',
  plus: '<path d="M12 5v14M5 12h14"/>',
  file: '<path d="M14 2H5v20h14V7Z"/><path d="M14 2v6h5M8 12h8M8 16h5"/>',
  layers: '<rect x="6" y="3" width="15" height="16" rx="2"/><path d="M3 6v14a2 2 0 0 0 2 2h13M10 8h7M10 12h5"/>',
  search: '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
  'align-left': '<path d="M4 5h16M4 10h11M4 15h16M4 20h10"/>',
  message: '<path d="M21 11a9 9 0 0 1-9 9H4l-3 2 2-6a9 9 0 1 1 18-5Z"/><path d="M7 10h10M7 14h6"/>',
  'check-square': '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="m7 12 3 3 7-7"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/>',
  check: '<path d="m5 12 4 4L19 6"/>',
  edit: '<path d="m14 4 6 6M3 21l5-1L21 7a2 2 0 0 0-4-4L4 16Z"/>',
  rotate: '<path d="M3 11a9 9 0 1 1 2 7M3 4v7h7"/>',
  upload: '<path d="M12 16V3m-5 5 5-5 5 5M3 15v6h18v-6"/>',
  close: '<path d="m6 6 12 12M6 18 18 6"/>',
};

function renderIcons(root = document) {
  root.querySelectorAll('[data-icon]').forEach(node => {
    node.innerHTML = `<svg class="icon" viewBox="0 0 24 24" aria-hidden="true">${iconPaths[node.dataset.icon] || iconPaths.file}</svg>`;
  });
}

const materials = [
  { id: 'biologi', title: 'Sistem Pernapasan Manusia', subject: 'Biologi', kind: 'file', format: 'PDF', pages: '24 halaman', date: '2 jam lalu', filename: 'Sistem Pernapasan.pdf', size: '2,4 MB', heading: 'Gimana caranya kita bernapas?', lead: 'Setiap tarikan napas membawa oksigen ke dalam tubuh. Sistem pernapasan bekerja untuk menukar oksigen dan karbon dioksida, agar sel bisa menghasilkan energi.', key: 'Bernapas bukan sekadar menghirup udara. Ada proses pertukaran gas yang menghubungkan paru-paru, darah, dan seluruh sel tubuh.', sections: [['Perjalanan udara masuk ke tubuh', 'Udara masuk melalui hidung, melewati faring, laring, dan trakea. Dari sana, udara mengalir ke bronkus, bronkiolus, lalu sampai di alveolus.'], ['Alveolus, tempat pertukaran gas', 'Dinding alveolus sangat tipis dan dikelilingi kapiler darah. Oksigen berdifusi ke dalam darah, sementara karbon dioksida bergerak sebaliknya untuk dikeluarkan.']], question: 'Di bagian mana pertukaran oksigen dan karbon dioksida terjadi?', answers: ['Trakea', 'Alveolus', 'Laring', 'Hidung'], explanation: 'Alveolus memiliki dinding tipis dan dikelilingi kapiler, sehingga oksigen dan karbon dioksida bisa berdifusi.', cards: [['Apa fungsi utama alveolus?', 'Tempat pertukaran oksigen dan karbon dioksida antara udara di paru-paru dan darah.'], ['Apa fungsi trakea?', 'Menyalurkan udara dari laring menuju bronkus.'], ['Apa yang terjadi saat inspirasi?', 'Diafragma berkontraksi, rongga dada membesar, dan udara masuk.'], ['Apa yang mengangkut oksigen dalam darah?', 'Hemoglobin di dalam sel darah merah.'], ['Mengapa dinding alveolus tipis?', 'Agar jarak difusi gas pendek sehingga pertukaran gas berlangsung efisien.'], ['Apa fungsi utama diafragma?', 'Mengubah volume rongga dada untuk membantu udara masuk dan keluar.']] },
  { id: 'ekonomi', title: 'Pengantar Ekonomi Mikro', subject: 'Ekonomi', kind: 'folder', format: 'Folder', pages: '3 file', date: 'Kemarin', filename: 'Pengantar Ekonomi Mikro', size: '3 file gabungan', heading: 'Bagaimana harga terbentuk?', lead: 'Ekonomi mikro mempelajari keputusan konsumen dan produsen. Interaksi permintaan dan penawaran membantu menjelaskan harga serta jumlah barang yang diperdagangkan.', key: 'Harga keseimbangan terbentuk ketika jumlah barang yang diminta sama dengan jumlah yang ditawarkan.', sections: [['Permintaan dan keputusan konsumen', 'Dengan faktor lain tetap, harga yang lebih tinggi cenderung mengurangi jumlah yang diminta. Pendapatan, selera, dan harga barang lain juga memengaruhi permintaan.'], ['Penawaran dan keseimbangan pasar', 'Produsen menyesuaikan jumlah barang yang ditawarkan terhadap harga dan biaya. Keseimbangan terjadi di perpotongan kurva permintaan dan penawaran.']], question: 'Kapan keseimbangan pasar terjadi?', answers: ['Saat harga paling tinggi', 'Saat jumlah diminta sama dengan jumlah ditawarkan', 'Saat tidak ada penjual', 'Saat semua barang gratis'], explanation: 'Pada harga keseimbangan, jumlah yang ingin dibeli konsumen sama dengan jumlah yang ingin dijual produsen.', cards: [['Apa itu permintaan?', 'Jumlah barang yang ingin dan mampu dibeli konsumen pada berbagai tingkat harga.'], ['Apa itu penawaran?', 'Jumlah barang yang bersedia dijual produsen pada berbagai tingkat harga.'], ['Apa itu harga keseimbangan?', 'Harga ketika jumlah diminta sama dengan jumlah ditawarkan.'], ['Apa arti kelangkaan?', 'Sumber daya terbatas dibandingkan kebutuhan dan keinginan.'], ['Apa itu biaya peluang?', 'Nilai alternatif terbaik yang dilepas ketika membuat pilihan.'], ['Apa arti elastisitas harga permintaan?', 'Ukuran respons jumlah diminta terhadap perubahan harga.']] },
  { id: 'statistika', title: 'Statistika Dasar', subject: 'Matematika', kind: 'file', format: 'PPTX', pages: '18 slide', date: '31 Okt', filename: 'Statistika Dasar.pptx', size: '1,8 MB', heading: 'Membaca cerita di balik angka', lead: 'Statistika membantu kita merangkum dan memahami data. Ukuran pemusatan memberi gambaran nilai yang mewakili sekumpulan pengamatan.', key: 'Mean, median, dan modus menjelaskan pusat data dari sudut berbeda. Pilih ukuran sesuai bentuk dan sebaran data.', sections: [['Mean dan median', 'Mean dihitung dari jumlah seluruh nilai dibagi banyaknya data. Median adalah nilai tengah setelah data diurutkan.'], ['Modus dan pencilan', 'Modus adalah nilai yang paling sering muncul. Pencilan dapat menggeser mean secara besar, sementara median biasanya lebih tahan terhadap pengaruhnya.']], question: 'Apa sebutan untuk nilai tengah setelah data diurutkan?', answers: ['Mean', 'Median', 'Modus', 'Rentang'], explanation: 'Median menunjukkan posisi tengah data yang telah diurutkan, bukan rata-rata aritmetik.', cards: [['Bagaimana menghitung mean?', 'Jumlahkan semua nilai, lalu bagi dengan banyaknya data.'], ['Apa itu median?', 'Nilai tengah data yang telah diurutkan.'], ['Apa itu modus?', 'Nilai yang paling sering muncul.'], ['Apa itu rentang?', 'Selisih antara nilai terbesar dan terkecil.'], ['Apa itu pencilan?', 'Nilai yang jauh berbeda dari kebanyakan data lainnya.'], ['Ukuran apa yang lebih tahan terhadap pencilan?', 'Median, karena ditentukan posisi data, bukan besarnya semua nilai.']] },
  { id: 'sel', title: 'Struktur dan Fungsi Sel', subject: 'Biologi', kind: 'file', format: 'PDF', pages: '16 halaman', date: '30 Okt' },
  { id: 'pasar', title: 'Permintaan dan Penawaran', subject: 'Ekonomi', kind: 'file', format: 'DOCX', pages: '12 halaman', date: '29 Okt' },
  { id: 'peluang', title: 'Pengantar Peluang', subject: 'Matematika', kind: 'folder', format: 'Folder', pages: '2 file', date: '28 Okt' },
  { id: 'ekosistem', title: 'Ekosistem dan Lingkungan', subject: 'Biologi', kind: 'file', format: 'PDF', pages: '20 halaman', date: '27 Okt' },
  { id: 'metode', title: 'Metode Penelitian', subject: 'Umum', kind: 'file', format: 'PDF', pages: '32 halaman', date: '26 Okt' },
];

const notes = new Map([['biologi', 'Alveolus itu seperti tempat tukar: oksigen masuk ke darah, karbon dioksida keluar.']]);
let currentMaterial = materials[0];
let currentTab = 'summary';
let filter = 'all';
let selectedAnswer = null;
let cardIndex = 0;
let cardFlipped = false;
let toastTimer;

function toast(message) {
  const el = document.querySelector('#toast');
  clearTimeout(toastTimer);
  el.textContent = message;
  el.hidden = false;
  toastTimer = setTimeout(() => { el.hidden = true; }, 3600);
}

function materialMeta(material) {
  return `${material.subject} / ${material.kind === 'folder' ? 'Folder, ' : material.format + ', '}${material.pages}`;
}

function renderLibrary() {
  const query = document.querySelector('#material-search').value.trim().toLocaleLowerCase('id');
  const list = document.querySelector('#library-list');
  const matches = materials.filter(m => (filter === 'all' || m.kind === filter) && `${m.title} ${m.subject} ${m.format}`.toLocaleLowerCase('id').includes(query));
  list.replaceChildren();
  matches.forEach(material => {
    const link = document.createElement('a');
    link.href = `#study?material=${material.id}`;
    link.className = 'material-row';
    link.innerHTML = `<span class="file-icon" data-icon="${material.kind === 'folder' ? 'folder' : 'file'}"></span><div class="material-name"><strong></strong><span></span></div><span class="row-date"></span><span class="row-open">Buka<span data-icon="arrow-up-right"></span></span>`;
    link.querySelector('strong').textContent = material.title;
    link.querySelector('.material-name>span').textContent = materialMeta(material);
    link.querySelector('.row-date').textContent = material.date;
    list.append(link);
  });
  renderIcons(list);
  document.querySelector('#library-count').textContent = `${matches.length} materi${query ? ' ditemukan' : ''}`;
  document.querySelector('#empty-search').hidden = matches.length > 0;
}

function showTab(tab, updateHash = true) {
  const tabs = ['summary', 'chat', 'quiz', 'cards'];
  if (!tabs.includes(tab)) tab = 'summary';
  currentTab = tab;
  document.querySelectorAll('.study-tab').forEach(button => {
    const active = button.dataset.tab === tab;
    button.classList.toggle('active', active);
    button.setAttribute('aria-selected', String(active));
    button.tabIndex = active ? 0 : -1;
  });
  tabs.forEach(key => { document.querySelector(`#panel-${key}`).hidden = key !== tab; });
  if (updateHash) history.replaceState(null, '', `#study?material=${currentMaterial.id}&tab=${tab}`);
}

function renderCard() {
  const pair = currentMaterial.cards[cardIndex];
  document.querySelector('#card-counter').textContent = `Kartu ${cardIndex + 1} / ${currentMaterial.cards.length}`;
  document.querySelector('#card-side').textContent = cardFlipped ? 'Jawaban' : 'Pertanyaan';
  document.querySelector('#card-text').textContent = pair[cardFlipped ? 1 : 0];
  document.querySelector('.flip-hint').innerHTML = `<span data-icon="rotate"></span>${cardFlipped ? 'Lihat pertanyaan' : 'Lihat jawaban'}`;
  document.querySelector('#flashcard').setAttribute('aria-label', cardFlipped ? 'Tampilkan pertanyaan' : 'Tampilkan jawaban');
  document.querySelector('#previous-card').disabled = cardIndex === 0;
  document.querySelector('#next-card').disabled = cardIndex === currentMaterial.cards.length - 1;
  renderIcons(document.querySelector('.flip-hint'));
}

function renderStudy(id) {
  const material = materials.find(m => m.id === id) || materials[0];
  // Remaining library entries deliberately open an explicit sample, never mismatched subject content.
  if (!material.heading) {
    toast('Detail materi ini belum dibuat. Menampilkan contoh Biologi.');
    history.replaceState(null, '', '#study?material=biologi');
    currentMaterial = materials[0];
  } else currentMaterial = material;
  const m = currentMaterial;
  document.querySelector('#study-title').textContent = m.title;
  document.querySelector('#study-meta').textContent = materialMeta(m);
  document.querySelector('#summary-title').textContent = m.heading;
  document.querySelector('#summary-lead').textContent = m.lead;
  document.querySelector('#key-idea').textContent = m.key;
  document.querySelector('#section-one-title').textContent = m.sections[0][0];
  document.querySelector('#section-one-body').textContent = m.sections[0][1];
  document.querySelector('#section-two-title').textContent = m.sections[1][0];
  document.querySelector('#section-two-body').textContent = m.sections[1][1];
  document.querySelector('#air-flow').hidden = m.id !== 'biologi';
  document.querySelectorAll('.source-ref').forEach(el => { el.hidden = m.id !== 'biologi'; });
  document.querySelector('#context-filename').textContent = m.filename;
  document.querySelector('#context-filemeta').textContent = m.kind === 'folder' ? m.size : `${m.pages} / ${m.size}`;
  document.querySelector('#source-filename').textContent = m.filename;
  document.querySelector('#personal-note').value = notes.get(m.id) || '';
  document.querySelector('#note-status').textContent = 'Contoh catatan pribadi';
  document.querySelector('#quiz-question').textContent = m.question;
  document.querySelectorAll('.answer-label').forEach((el, i) => { el.textContent = m.answers[i]; });
  document.querySelectorAll('[data-answer]').forEach(el => { el.classList.remove('selected'); el.setAttribute('aria-pressed', 'false'); });
  selectedAnswer = null;
  document.querySelector('#check-answer').disabled = true;
  document.querySelector('#quiz-result').hidden = true;
  document.querySelector('#chat-messages').innerHTML = '<div class="chat-answer"><span class="chat-author">RUANG BELAJAR</span><p>Bagian mana yang belum kebayang? Pilih contoh pertanyaan di bawah, atau tulis sendiri.</p></div>';
  document.querySelector('#chat-question').value = '';
  cardIndex = 0;
  cardFlipped = false;
  renderCard();
}

function route() {
  const [path, query = ''] = location.hash.slice(1).split('?');
  const valid = ['landing', 'login', 'register', 'forgot', 'home', 'library', 'study', 'history', 'rooms', 'guide'];
  const screen = valid.includes(path) ? path : 'landing';
  const isGuest = ['landing', 'login', 'register', 'forgot'].includes(screen);
  document.body.classList.toggle('guest-view', isGuest);
  document.body.classList.toggle('workspace-view', !isGuest);
  document.body.classList.toggle('landing-view', screen === 'landing');
  document.body.classList.toggle('auth-view', isGuest && screen !== 'landing');
  const params = new URLSearchParams(query);
  document.querySelectorAll('.screen').forEach(el => { el.hidden = el.id !== `screen-${screen}`; });
  document.querySelectorAll('[data-nav]').forEach(link => {
    const active = link.dataset.nav === (screen === 'study' ? 'library' : screen);
    link.classList.toggle('active', active);
    if (active) link.setAttribute('aria-current', 'page');
    else link.removeAttribute('aria-current');
  });
  const labels = { landing: 'Mulai belajar', login: 'Masuk', register: 'Daftar', forgot: 'Lupa kata sandi', home: 'Beranda', library: 'Materi saya', study: 'Materi saya / Belajar', history: 'Riwayat belajar', rooms: 'Belajar bareng', guide: 'Panduan' };
  document.querySelector('#breadcrumb strong').textContent = labels[screen];
  document.title = `${labels[screen]} - Konsep Ruang Belajar`;
  if (screen === 'study') {
    renderStudy(params.get('material') || 'biologi');
    showTab(params.get('tab') || 'summary', false);
  }
  if (screen === 'library') renderLibrary();
  const section = params.get('section');
  if (screen === 'landing' && ['how', 'features'].includes(section)) {
    requestAnimationFrame(() => document.getElementById(section).scrollIntoView({ block: 'start' }));
  } else window.scrollTo(0, 0);
}

renderIcons();
document.querySelectorAll('[data-tab]').forEach(button => button.addEventListener('click', () => showTab(button.dataset.tab)));
document.querySelectorAll('.study-tab').forEach(button => button.addEventListener('keydown', event => {
  const tabs = [...document.querySelectorAll('.study-tab')];
  const index = tabs.indexOf(button);
  let target;
  if (event.key === 'ArrowRight') target = tabs[(index + 1) % tabs.length];
  if (event.key === 'ArrowLeft') target = tabs[(index - 1 + tabs.length) % tabs.length];
  if (event.key === 'Home') target = tabs[0];
  if (event.key === 'End') target = tabs[tabs.length - 1];
  if (target) { event.preventDefault(); target.focus(); showTab(target.dataset.tab); }
}));
document.querySelector('#material-search').addEventListener('input', renderLibrary);
document.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click', () => {
  filter = button.dataset.filter;
  document.querySelectorAll('[data-filter]').forEach(el => {
    el.classList.toggle('active', el === button);
    el.setAttribute('aria-pressed', String(el === button));
  });
  renderLibrary();
}));
document.querySelectorAll('[data-answer]').forEach(button => button.addEventListener('click', () => {
  selectedAnswer = Number(button.dataset.answer);
  document.querySelectorAll('[data-answer]').forEach(el => {
    el.classList.toggle('selected', el === button);
    el.setAttribute('aria-pressed', String(el === button));
  });
  document.querySelector('#check-answer').disabled = false;
  document.querySelector('#quiz-result').hidden = true;
}));
document.querySelector('#check-answer').addEventListener('click', () => {
  const result = document.querySelector('#quiz-result');
  result.textContent = `${selectedAnswer === 1 ? 'Iya, betul!' : 'Belum pas. Jawaban yang benar: ' + currentMaterial.answers[1] + '.'} ${currentMaterial.explanation}`;
  result.hidden = false;
});
document.querySelector('#flashcard').addEventListener('click', () => { cardFlipped = !cardFlipped; renderCard(); });
document.querySelector('#previous-card').addEventListener('click', () => { cardIndex = Math.max(0, cardIndex - 1); cardFlipped = false; renderCard(); });
document.querySelector('#next-card').addEventListener('click', () => { cardIndex = Math.min(currentMaterial.cards.length - 1, cardIndex + 1); cardFlipped = false; renderCard(); });
document.querySelector('#save-note').addEventListener('click', () => {
  notes.set(currentMaterial.id, document.querySelector('#personal-note').value);
  document.querySelector('#note-status').textContent = 'Tersimpan selama pratinjau ini. Hilang setelah reload.';
});
document.querySelectorAll('[data-prompt]').forEach(button => button.addEventListener('click', () => {
  document.querySelector('#chat-question').value = button.dataset.prompt;
  document.querySelector('#chat-form').requestSubmit();
}));
document.querySelector('#chat-form').addEventListener('submit', event => {
  event.preventDefault();
  const input = document.querySelector('#chat-question');
  const question = input.value.trim();
  if (!question) return;
  const chat = document.querySelector('#chat-messages');
  const message = document.createElement('p');
  message.className = 'chat-question';
  message.textContent = question;
  const answer = document.createElement('div');
  answer.className = 'chat-answer';
  answer.innerHTML = '<span class="chat-author">Contoh jawaban / belum terhubung AI</span><p></p>';
  answer.querySelector('p').textContent = currentMaterial.key;
  chat.append(message, answer);
  input.value = '';
  chat.scrollTop = chat.scrollHeight;
});
document.querySelectorAll('[data-upload]').forEach(button => button.addEventListener('click', () => document.querySelector('#upload-dialog').showModal()));
document.querySelectorAll('[data-close-dialog]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
document.querySelector('#source-button').addEventListener('click', () => document.querySelector('#source-dialog').showModal());
document.querySelector('#upload-input').addEventListener('change', event => {
  const files = event.target.files;
  const oversized = [...files].some(file => file.size > 20 * 1024 * 1024);
  document.querySelector('#upload-filename').textContent = oversized ? 'Ada file melebihi 20 MB. Pilih file lain.' : (files.length === 1 ? files[0].name : files.length ? `${files.length} file dipilih` : 'Pilih file kamu');
  document.querySelector('#upload-demo').disabled = !files.length || oversized;
});
document.querySelector('#upload-demo').addEventListener('click', () => {
  document.querySelector('#upload-dialog').close();
  location.hash = '#study';
  toast('Ini contoh hasil dari materi Biologi. File kamu tidak diunggah.');
});
window.addEventListener('hashchange', route);
route();
