@extends('layouts.guest')

@section('title', 'Ruang Belajar - Pelan-pelan, makin paham')
@section('body-class', 'landing-view')

@section('content')
<section class="screen" id="screen-landing" aria-labelledby="landing-title">
          <div class="landing-hero">
            <div class="garden-scene" data-garden-scene aria-hidden="true"></div>
            <div class="hero-copy">
              <h1 id="landing-title">Belajar boleh<br><span>pelan-pelan</span></h1>
              <p class="hero-description">Bawa materi yang bikin kamu penasaran.<br>Kita ringkas, bahas, dan latihan sampai makin paham.</p>
              <div class="garden-shortcuts" aria-label="Kenali fitur belajar"><a href="#features"><span data-icon="align-left"></span>Ambil intinya</a><a href="#features"><span data-icon="message"></span>Bahas yang bingung</a><a href="#features"><span data-icon="layers"></span>Ingat lebih lama</a></div>
              <div class="hero-actions"><a href="{{ route('register') }}" class="button button-primary">Yuk, mulai belajar<span data-icon="arrow-right"></span></a><a href="#how" class="hero-how-link">Lihat caranya<span data-icon="arrow-down"></span></a></div>
              <p class="hero-footnote">Dari PDF, slide, sampai foto catatanmu.</p>
            </div>
            <div class="garden-caption"><span data-icon="book-open"></span>Satu halaman dulu. Sisanya menyusul.</div>
            <button class="motion-toggle" type="button" data-motion-toggle aria-pressed="false" aria-label="Jeda animasi"><span data-icon="pause"></span></button>
          </div>

          <section class="garden-demo-section" aria-labelledby="garden-demo-title">
            <div class="garden-demo-copy"><p class="eyebrow">Materimu tetap jadi titik mulainya</p><h2 id="garden-demo-title">Dari "nggak ngerti"<br>jadi "oh, gitu!"</h2><p>Bukan cuma tempat menyimpan file. Di sini kamu bisa pelan-pelan mengurai isi materi, satu pertanyaan setiap kali.</p><div class="garden-example-note"><span data-icon="hand"></span><span>Coba empat cara belajar<br>di contoh ini.</span></div></div>
            <div class="hero-example" aria-label="Contoh belajar dari materi Biologi">
              <div class="example-source"><span class="example-file-icon" data-icon="file"></span><div><strong>Sistem Pernapasan.pdf</strong><span>Contoh materi Biologi.</span></div><span class="source-check" data-icon="check"></span></div>
              <div class="example-connection" aria-hidden="true"><span></span><span data-icon="arrow-down"></span></div>
              <div class="example-window">
                <div class="example-window-heading"><span data-icon="book-open"></span><strong>Yuk, pahami isinya.</strong><span class="example-badge">Contoh</span></div>
                <div class="example-tabs" role="tablist" aria-label="Coba contoh fitur"><button id="demo-tab-summary" role="tab" aria-selected="true" aria-controls="example-content" data-demo-tab="summary" class="active">Ringkasan</button><button id="demo-tab-chat" role="tab" aria-selected="false" aria-controls="example-content" tabindex="-1" data-demo-tab="chat">Tanya AI</button><button id="demo-tab-quiz" role="tab" aria-selected="false" aria-controls="example-content" tabindex="-1" data-demo-tab="quiz">Latihan soal</button><button id="demo-tab-cards" role="tab" aria-selected="false" aria-controls="example-content" tabindex="-1" data-demo-tab="cards">Kartu belajar</button></div>
                <div id="example-content" class="example-content" role="tabpanel" aria-labelledby="demo-tab-summary" tabindex="0"><p class="example-overline" id="example-overline">Mulai dari intinya</p><h2 id="example-title">Gimana caranya<br>kita bernapas?</h2><p id="example-description">Oksigen masuk lewat hidung, lalu menuju paru-paru. Di alveolus, oksigen bertukar tempat dengan karbon dioksida.</p><div class="example-takeaway"><span data-icon="check"></span><p id="example-takeaway">Alveolus itu tempat pertukaran gas, bukan sekadar kantong udara.</p></div><p class="example-source-note" id="example-source-note">Contoh ringkasan materi Biologi</p></div>
              </div>
              <p class="example-caption"><span data-icon="hand"></span>Coba klik tabnya. Belajarnya bisa beda-beda.</p>
            </div>
          </section>

          <section class="how-section" id="how" aria-labelledby="how-title"><div class="landing-section-heading"><p class="eyebrow">Nggak perlu mulai dari nol</p><h2 id="how-title">Materinya dari kamu<br>Bantuan belajarnya di sini</h2></div><div class="how-steps"><article><span class="step-marker">1</span><h3>Bawa materimu</h3><p>Upload file yang mau dipelajari. Beberapa file satu topik? Jadikan satu folder.</p></article><article><span class="step-marker">2</span><h3>Pilih cara belajarnya</h3><p>Mau baca ringkasan, tanya, atau langsung latihan? Pilih sesuai kebutuhanmu.</p></article><article><span class="step-marker">3</span><h3>Balik lagi kapan pun</h3><p>Materi dan hasil belajarmu tersimpan. Tinggal lanjut dari tempat terakhir.</p></article></div></section>

          <section class="features-section" id="features" aria-labelledby="features-title"><div class="features-intro"><p class="eyebrow">Belajar nggak harus satu cara</p><h2 id="features-title">Lagi butuh<br>bantuan apa?</h2><p>Mulai dari yang paling kamu butuhkan. Semua tetap nyambung ke materi yang kamu buka.</p><a href="{{ route('register') }}" class="text-link">Mulai dari materimu<span data-icon="arrow-right"></span></a></div><div class="features-list"><article><span data-icon="align-left"></span><div><h3>"Mau tahu inti materinya dulu."</h3><p><strong>Ringkasan</strong> bantu ambil poin penting tanpa baca ulang semuanya.</p></div></article><article><span data-icon="message"></span><div><h3>"Bagian ini maksudnya apa, ya?"</h3><p><strong>Tanya AI</strong> buat bahas bagian yang belum kebayang.</p></div></article><article><span data-icon="check-square"></span><div><h3>"Sudah paham belum, ya?"</h3><p><strong>Latihan soal</strong> buat cek jawaban dan pelajari pembahasannya.</p></div></article><article><span data-icon="layers"></span><div><h3>"Kemarin ingat, sekarang lupa."</h3><p><strong>Kartu belajar</strong> bantu ulang konsep sedikit demi sedikit.</p></div></article></div></section>

          <footer class="guest-footer"><a href="{{ url('/') }}" class="guest-brand">ruangbelajar</a><p>Pelan-pelan, yang penting makin paham.</p></footer>
        </section>
@endsection
