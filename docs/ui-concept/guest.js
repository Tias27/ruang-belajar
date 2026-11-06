const exampleModes = {
  summary: { overline: 'Mulai dari intinya', title: 'Gimana caranya kita bernapas?', description: 'Oksigen masuk lewat hidung, lalu menuju paru-paru. Di alveolus, oksigen bertukar tempat dengan karbon dioksida.', takeaway: 'Alveolus itu tempat pertukaran gas, bukan sekadar kantong udara.', source: 'Diringkas dari materi, halaman 3-12' },
  chat: { overline: 'Ada yang bikin bingung?', title: 'Kenapa alveolus tipis banget?', description: 'Bayangkan oksigen harus menyeberang tembok. Makin tipis temboknya, makin dekat jarak yang perlu dilewati. Dinding alveolus yang tipis memudahkan gas masuk ke darah.', takeaway: 'Tanya dengan bahasamu sendiri. Jawaban mengacu pada materi yang kamu buka.', source: 'Contoh jawaban berdasarkan materi, halaman 9-12' },
  quiz: { overline: 'Coba cek pemahamanmu', title: 'Pertukaran gas terjadi di mana?', description: 'A. Trakea     B. Alveolus     C. Hidung', takeaway: 'Jawaban: B. Alveolus. Dindingnya tipis dan dikelilingi kapiler darah.', source: 'Contoh soal beserta penjelasannya' },
  cards: { overline: 'Biar makin nempel', title: 'Apa fungsi utama alveolus?', description: 'Coba jawab dulu dalam pikiran. Kartu belajar bantu kamu mengulang konsep yang penting.', takeaway: 'Jawabannya: tempat pertukaran oksigen dan karbon dioksida antara paru-paru dan darah.', source: 'Contoh kartu tanya jawab dari materi Biologi' },
};

function setExampleMode(mode) {
  const content = exampleModes[mode];
  document.querySelectorAll('[data-demo-tab]').forEach(button => {
    const selected = button.dataset.demoTab === mode;
    button.classList.toggle('active', selected);
    button.setAttribute('aria-selected', String(selected));
    button.tabIndex = selected ? 0 : -1;
  });
  document.querySelector('#example-content').setAttribute('aria-labelledby', `demo-tab-${mode}`);
  document.querySelector('#example-overline').textContent = content.overline;
  document.querySelector('#example-title').textContent = content.title;
  document.querySelector('#example-description').textContent = content.description;
  document.querySelector('#example-takeaway').textContent = content.takeaway;
  document.querySelector('#example-source-note').textContent = content.source;
}

document.querySelectorAll('[data-demo-tab]').forEach(button => {
  button.addEventListener('click', () => setExampleMode(button.dataset.demoTab));
  button.addEventListener('keydown', event => {
    const tabs = [...document.querySelectorAll('[data-demo-tab]')];
    const index = tabs.indexOf(button);
    const next = { ArrowRight: (index + 1) % 4, ArrowLeft: (index + 3) % 4, Home: 0, End: 3 }[event.key];
    if (next === undefined) return;
    event.preventDefault();
    tabs[next].focus();
    setExampleMode(tabs[next].dataset.demoTab);
  });
});

document.querySelectorAll('[data-password-toggle]').forEach(button => button.addEventListener('click', () => {
  const input = document.getElementById(button.dataset.passwordToggle);
  const reveal = input.type === 'password';
  input.type = reveal ? 'text' : 'password';
  button.setAttribute('aria-label', reveal ? 'Sembunyikan kata sandi' : 'Lihat kata sandi');
  button.setAttribute('aria-pressed', String(reveal));
  button.innerHTML = `<span data-icon="${reveal ? 'eye-off' : 'eye'}"></span>`;
  renderIcons(button);
}));

const captchaExamples = { login: [7, 2], register: [4, 3] };
function showFieldError(id, message) {
  const field = document.getElementById(id);
  const label = document.getElementById(`${id}-error`);
  label.textContent = message;
  label.hidden = !message;
  field.setAttribute('aria-invalid', String(Boolean(message)));
}

document.querySelectorAll('[data-captcha-refresh]').forEach(button => button.addEventListener('click', () => {
  const kind = button.dataset.captchaRefresh;
  const previous = captchaExamples[kind];
  const first = previous[0] % 8 + 1;
  const second = previous[1] % 6 + 1;
  captchaExamples[kind] = [first, second];
  document.getElementById(`${kind}-captcha-question`).textContent = `${first} + ${second} =`;
  document.getElementById(`${kind}-captcha`).value = '';
  showFieldError(`${kind}-captcha`, '');
  document.getElementById(`${kind}-captcha`).focus();
}));

function validateGuestForm(form, kind) {
  let firstInvalid = null;
  const reject = (id, message) => {
    showFieldError(id, message);
    if (message && !firstInvalid) firstInvalid = document.getElementById(id);
  };
  const email = form.elements.email;
  if (email) reject(email.id, !email.value.trim() ? 'Isi emailmu dulu, ya.' : email.validity.typeMismatch ? 'Format emailnya belum pas. Contoh: nama@email.com.' : '');
  const username = form.elements.username;
  if (username) {
    const value = username.value.trim();
    let error = !value ? 'Isi nama penggunamu dulu, ya.' : '';
    if (kind === 'register' && value && (value.length < 3 || !/^[\p{L}\p{N} ._-]+$/u.test(value))) error = 'Minimal 3 karakter. Pakai huruf, angka, spasi, titik, _ atau -.';
    reject(username.id, error);
  }
  const password = form.elements.password;
  if (password) reject(password.id, !password.value ? 'Kata sandinya belum diisi.' : kind === 'register' && password.value.length < 8 ? 'Kata sandinya perlu minimal 8 karakter.' : '');
  const confirmation = form.elements.password_confirmation;
  if (confirmation) reject(confirmation.id, !confirmation.value ? 'Ketik kata sandimu sekali lagi.' : confirmation.value !== password.value ? 'Belum sama. Coba cek lagi, ya.' : '');
  const captcha = form.elements.math_captcha;
  if (captcha) {
    const expected = captchaExamples[kind][0] + captchaExamples[kind][1];
    const answer = captcha.value.trim();
    reject(captcha.id, !answer ? 'Isi hasil hitungannya dulu.' : !/^\d+$/.test(answer) || Number(answer) !== expected ? 'Belum pas. Coba hitung sekali lagi, ya.' : '');
  }
  if (firstInvalid) firstInvalid.focus();
  return !firstInvalid;
}

// Preview only: credentials never leave this page or enter persistent storage.
for (const kind of ['login', 'register']) {
  const form = document.getElementById(`${kind}-form`);
  form.addEventListener('submit', event => {
    event.preventDefault();
    if (!validateGuestForm(form, kind)) return;
    form.reset();
    form.querySelectorAll('[data-password-toggle]').forEach(button => {
      document.getElementById(button.dataset.passwordToggle).type = 'password';
      button.setAttribute('aria-pressed', 'false');
      button.setAttribute('aria-label', 'Lihat kata sandi');
      button.innerHTML = '<span data-icon="eye"></span>';
      renderIcons(button);
    });
    location.hash = '#home';
    toast(kind === 'login' ? 'Contoh beranda terbuka. Ini belum login sungguhan.' : 'Contoh beranda terbuka. Tidak ada akun yang dibuat.');
  });
}

document.querySelectorAll('.guest-form input').forEach(input => input.addEventListener('input', () => {
  if (document.getElementById(`${input.id}-error`)) showFieldError(input.id, '');
  document.getElementById('reset-demo-message').hidden = true;
}));

document.querySelector('#forgot-form').addEventListener('submit', event => {
  event.preventDefault();
  if (!validateGuestForm(event.target, 'forgot')) return;
  event.target.reset();
  document.getElementById('reset-demo-message').hidden = false;
});
