(() => {
  const exampleModes = {
    summary: { overline: 'Mulai dari intinya', title: 'Gimana caranya kita bernapas?', description: 'Oksigen masuk lewat hidung, lalu menuju paru-paru. Di alveolus, oksigen bertukar tempat dengan karbon dioksida.', takeaway: 'Alveolus itu tempat pertukaran gas, bukan sekadar kantong udara.', source: 'Contoh ringkasan materi Biologi' },
    chat: { overline: 'Ada yang bikin bingung?', title: 'Kenapa alveolus tipis banget?', description: 'Bayangkan oksigen harus menyeberang tembok. Makin tipis temboknya, makin dekat jarak yang perlu dilewati. Dinding alveolus yang tipis memudahkan gas masuk ke darah.', takeaway: 'Tanya dengan bahasamu sendiri. Jawaban mengacu pada materi yang kamu buka.', source: 'Contoh tanya jawab tentang materi Biologi' },
    quiz: { overline: 'Coba cek pemahamanmu', title: 'Pertukaran gas terjadi di mana?', description: 'A. Trakea     B. Alveolus     C. Hidung', takeaway: 'Jawaban: B. Alveolus. Dindingnya tipis dan dikelilingi kapiler darah.', source: 'Contoh soal beserta penjelasannya' },
    cards: { overline: 'Biar makin nempel', title: 'Apa fungsi utama alveolus?', description: 'Coba jawab dulu dalam pikiran. Kartu belajar bantu kamu mengulang konsep yang penting.', takeaway: 'Jawabannya: tempat pertukaran oksigen dan karbon dioksida antara paru-paru dan darah.', source: 'Contoh kartu tanya jawab dari materi Biologi' },
  };

  const tabs = [...document.querySelectorAll('[data-demo-tab]')];
  function setExampleMode(mode) {
    const content = exampleModes[mode];
    if (!content) return;
    tabs.forEach(button => {
      const selected = button.dataset.demoTab === mode;
      button.classList.toggle('active', selected);
      button.setAttribute('aria-selected', String(selected));
      button.tabIndex = selected ? 0 : -1;
    });
    document.getElementById('example-content').setAttribute('aria-labelledby', `demo-tab-${mode}`);
    for (const [field, value] of Object.entries(content)) {
      const id = field === 'source' ? 'example-source-note' : `example-${field}`;
      document.getElementById(id).textContent = value;
    }
  }
  tabs.forEach(button => {
    button.addEventListener('click', () => setExampleMode(button.dataset.demoTab));
    button.addEventListener('keydown', event => {
      const index = tabs.indexOf(button);
      const next = { ArrowRight: (index + 1) % tabs.length, ArrowLeft: (index + tabs.length - 1) % tabs.length, Home: 0, End: tabs.length - 1 }[event.key];
      if (next === undefined) return;
      event.preventDefault();
      tabs[next].focus();
      setExampleMode(tabs[next].dataset.demoTab);
    });
  });

  document.querySelectorAll('[data-password-toggle]').forEach(button => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.passwordToggle);
      const reveal = input.type === 'password';
      input.type = reveal ? 'text' : 'password';
      button.setAttribute('aria-label', reveal ? 'Sembunyikan kata sandi' : 'Lihat kata sandi');
      button.setAttribute('aria-pressed', String(reveal));
      button.innerHTML = `<span data-icon="${reveal ? 'eye-off' : 'eye'}"></span>`;
      renderIcons(button);
    });
  });

  document.querySelectorAll('[data-captcha-refresh]').forEach(button => {
    button.addEventListener('click', async () => {
      const kind = button.dataset.captchaRefresh;
      const answer = document.getElementById(`${kind}-captcha`);
      const error = document.getElementById(`${kind}-captcha-error`);
      button.disabled = true;
      try {
        const response = await fetch(button.dataset.captchaUrl, {
          credentials: 'same-origin',
          headers: { Accept: 'application/json' },
          cache: 'no-store',
        });
        if (!response.ok) throw new Error('Captcha refresh failed');
        const captcha = await response.json();
        if (!Number.isInteger(captcha.num1) || !Number.isInteger(captcha.num2) || !['+', '-', '*'].includes(captcha.operator)) {
          throw new Error('Invalid captcha response');
        }
        document.getElementById(`${kind}-captcha-question`).textContent = `${captcha.num1} ${captcha.operator} ${captcha.num2} =`;
        answer.value = '';
        answer.setAttribute('aria-invalid', 'false');
        error.textContent = '';
        error.hidden = true;
        answer.focus();
      } catch {
        error.textContent = 'Hitungan belum bisa diganti. Coba lagi, ya.';
        error.hidden = false;
      } finally {
        button.disabled = false;
      }
    });
  });

  const lock = document.querySelector('[data-login-lock]');
  if (lock) {
    const button = document.querySelector('#login-form button[type="submit"]');
    const until = Number(lock.dataset.loginLock) * 1000;
    function updateLock() {
      const seconds = Math.max(0, Math.ceil((until - Date.now()) / 1000));
      if (seconds > 0) {
        lock.querySelector('[data-lock-seconds]').textContent = String(seconds);
        window.setTimeout(updateLock, 1000);
        return;
      }
      lock.textContent = 'Sudah bisa coba masuk lagi.';
      button.disabled = false;
    }
    updateLock();
  }
})();
