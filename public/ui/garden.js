// Each moving part is a separate SVG group; the composition stays sharp on small screens.
const gardenArt = `
<svg class="garden-art" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 540" fill="none" aria-hidden="true">
  <defs>
    <pattern id="garden-paper" width="19" height="19" patternUnits="userSpaceOnUse"><circle cx="3" cy="4" r=".7" fill="#42695f" opacity=".1"/><circle cx="13" cy="12" r=".5" fill="#42695f" opacity=".08"/></pattern>
    <g id="garden-cloud"><path d="M0 31c8-12 24-16 40-11C45-5 81-9 98 10c23-10 47 0 50 18 18-4 34 1 40 10-57 6-123 6-188-1Z" fill="#fbfcf4"/><path d="M22 39h122M59 45h57" stroke="#f5f8ef" stroke-width="2" stroke-linecap="round"/></g>
    <g id="garden-grass" stroke="#658478" stroke-width="1.6" stroke-linecap="round"><path d="M0 14-3 2M0 14 6 5M0 14l-9-7M0 14l10-1"/></g>
    <g id="garden-flower"><path d="m0 8 1 21m-1-8-6-3m7 8 6-4" stroke="#799a80" stroke-width="1.5" stroke-linecap="round"/><path d="M-2 5c-9-6-13 4-6 7-3 7 7 10 9 3 8 3 13-6 5-9 4-7-4-10-8-1Z" fill="#fff8e6" stroke="#9d9c74" stroke-width=".8"/><circle cx="0" cy="10" r="2.5" fill="#d8bd69"/></g>
    <g id="garden-leaf"><path d="M0 0C-16-16-12-31 5-39 19-20 16-9 0 0Z" fill="#9dbba2" stroke="#5e8075" stroke-width="1.2"/><path d="M0 0 5-31" stroke="#688c7b" stroke-width="1"/></g>
    <g id="garden-tree">
      <path d="m-13 237 4-111 18-6 9 117 12 12c-17 6-39 6-54 0Z" fill="#b8aa8b" stroke="#658274" stroke-width="1.7"/>
      <path d="m1 212-3-68m5 28 22-28m-26 11-19-19" stroke="#8c9273" stroke-width="1.8" stroke-linecap="round"/>
      <g class="garden-canopy">
        <path d="M-5 4c-17-13-40-2-41 12-22-10-42 7-41 27-22-1-37 17-30 35-23 6-30 29-15 43-13 19-1 39 17 41 0 22 25 34 44 22 12 18 35 21 51 8 21 17 43 14 57-2 20 8 44-3 46-23 25 1 38-22 27-41 19-10 17-35 1-46 11-20-1-39-22-41C85 20 61 14 45 23 38 6 15-4-5 4Z" fill="#a7c2ad" stroke="#58776a" stroke-width="1.9" stroke-linejoin="round"/>
        <path d="M-110 117c15 12 33 10 42-1 12 23 34 25 49 12 19 16 41 14 49-3 16 15 38 11 47-2 8 4 18 4 27-1M-86 65c10 8 18 10 27 6M-46 49c9 2 15-1 20-7M22 46c10 10 21 10 31 4M-51 151c7 9 16 10 25 5M39 157c8 6 16 4 22-2" stroke="#7eaa8d" stroke-width="1.8" stroke-linecap="round"/>
        <path d="M-112 139c19 13 34 5 42-3 17 19 36 19 52 8 17 15 37 11 46-3 19 12 38 7 46-6 15 4 29 0 34-6-3 19-12 26-25 25-3 20-26 31-45 22-13 16-37 19-57 2-16 13-39 10-51-8-19 12-43 0-42-20Z" fill="#8eb299" opacity=".55"/>
        <path d="m-73 94 5-3m28-56 5 1m4 59 4-3m-17 43 5 2m60-55 4-2m30 40 4 1m-14-70 4-3m36 39 5 2M-90 142l5 1" stroke="#628c77" stroke-width="1.1" stroke-linecap="round"/>
      </g>
    </g>
  </defs>

  <g class="garden-cloud cloud-one" transform="translate(100 70) scale(.8)"><use href="#garden-cloud"/></g>
  <g class="garden-cloud cloud-two" transform="translate(1130 25) scale(.95)"><use href="#garden-cloud"/></g>
  <g class="garden-cloud cloud-three" transform="translate(880 165) scale(.45)"><use href="#garden-cloud"/></g>
  <g class="garden-birds" stroke="#829d98" stroke-width="1.5" stroke-linecap="round"><path d="m280 158 8-5 8 5m21-17 7-4 7 4M1150 153l7-4 7 4"/></g>

  <path d="M0 318c112-32 211-11 284 14 77-36 147-37 229-13 145-30 233-2 302 21 163-35 267-28 345-7 99-42 187-30 280-12v219H0Z" fill="#d8e4cf"/>
  <path d="M0 367c140-36 278-7 399 11 179-37 338-15 460 7 210-49 385-36 581-6v161H0Z" fill="#c9dcc4"/>
  <path d="M0 425c167-20 290-10 416 17M990 430c150-23 290-21 450-7" stroke="#b2c9b0" stroke-width="1.5"/>
  <g class="garden-waterway">
  <path d="M546 378c101-8 211-12 305-4-101 35-163 52-112 78 61 31 204 47 278 88H342c36-45 159-73 246-89 82-15 25-42-42-73Z" fill="#afced0"/>
  <path d="M546 378c101-8 211-12 305-4-101 35-163 52-112 78M588 451c82-15 25-42-42-73" stroke="#8eafb1" stroke-width="1.5"/>
  <g class="garden-water" stroke="#e4f0e8" stroke-width="1.4" stroke-linecap="round"><path d="M616 396h35m57-8h25m-87 35h30m-55 43h45m89 8h31m-252 27h48m197 18h36m-123-27h20m-178 36h25M707 443h23"/></g>
  <g class="garden-ripple" transform="translate(670 453)" stroke="#86afb1" stroke-width="1.2"><ellipse rx="23" ry="4"/><ellipse rx="35" ry="6" opacity=".5"/></g>
  </g>

  <g class="garden-trees-left">
  <g opacity=".5" transform="translate(40 110) scale(.75)"><use href="#garden-tree"/></g>
  <g transform="translate(160 40) scale(1.16)"><use href="#garden-tree"/></g>
  <g transform="translate(326 178) scale(.75)"><use href="#garden-tree"/></g>
  </g>
  <g class="garden-trees-right">
  <g opacity=".55" transform="translate(1373 105) scale(.89)"><use href="#garden-tree"/></g>
  <g transform="translate(1242 40) scale(1.25)"><use href="#garden-tree"/></g>
  <g transform="translate(1094 165) scale(.82)"><use href="#garden-tree"/></g>
  </g>

  <g class="garden-plant" transform="translate(46 378)"><path d="M0 40 3-13M1 14l-16-15m17 1 15-19" stroke="#658675" stroke-width="2"/><use href="#garden-leaf" transform="translate(0 12) rotate(-50) scale(.7)"/><use href="#garden-leaf" transform="translate(4 0) rotate(42) scale(.75)"/><use href="#garden-leaf" transform="translate(2 -7) scale(.7)"/></g>
  <g class="garden-plant plant-two" transform="translate(1348 368)"><path d="M0 53 2-5M1 29l-16-15m17 0 17-21" stroke="#658675" stroke-width="2"/><use href="#garden-leaf" transform="translate(1 27) rotate(-55) scale(.85)"/><use href="#garden-leaf" transform="translate(3 12) rotate(45) scale(.9)"/><use href="#garden-leaf" transform="translate(2 0) scale(.7)"/></g>

  <g class="garden-grasses"><use href="#garden-grass" transform="translate(99 394)"/><use href="#garden-grass" transform="translate(212 378) scale(.8)"/><use href="#garden-grass" transform="translate(360 395)"/><use href="#garden-grass" transform="translate(418 417) scale(.9)"/><use href="#garden-grass" transform="translate(973 402)"/><use href="#garden-grass" transform="translate(1163 391) scale(.8)"/><use href="#garden-grass" transform="translate(1295 414)"/><use href="#garden-grass" transform="translate(72 450) scale(.8)"/><use href="#garden-grass" transform="translate(1387 453) scale(.9)"/></g>
  <use href="#garden-flower" transform="translate(269 383)"/><use href="#garden-flower" transform="translate(289 391) scale(.7)"/><use href="#garden-flower" transform="translate(1042 383)"/><use href="#garden-flower" transform="translate(1060 397) scale(.7)"/>

  <g class="garden-reading-spot">
  <g transform="translate(435 365) rotate(-4)">
    <path d="m0 13 116-2 17 50-129 5Z" fill="#f4eedb" stroke="#82958a" stroke-width="1.6"/>
    <path d="m8 25 115-2M11 38l115-3M15 51l115-5M26 12l8 49m18-49 9 48m20-48 8 45m16-46 10 45" stroke="#d5cbb1" stroke-width="1.2"/>
  </g>

  <g class="garden-reader" transform="translate(508 309)">
    <ellipse cx="24" cy="105" rx="54" ry="8" fill="#acc4a9" opacity=".45"/>
    <g class="reader-breathe">
      <path d="M-8 91c-13 0-22 8-19 16 15 9 35 0 39-6m39-10c13 0 23 8 19 16-15 9-35 0-39-6" fill="#f7f1de" stroke="#667e70" stroke-width="1.7"/>
      <path d="M-3 54c4-14 41-19 49-1l8 43c-19 10-44 11-62-1Z" fill="#6e91bc" stroke="#577364" stroke-width="1.8"/>
      <path d="M8 59c-8 5-14 19-15 28m43-29c7 5 13 18 16 27" stroke="#5d7a9e" stroke-width="1.5"/>
      <g class="reader-head">
        <path d="M3 4C-16-29-3-47 8-25L16 1m12-2c0-34 19-45 21-20 1 13-6 26-9 31" fill="#faf4e5" stroke="#627b6c" stroke-width="1.8"/>
        <path d="M5-5C-2-24 0-31 3-25l8 22m22 1c2-22 10-31 11-20 0 8-5 17-7 22" stroke="#d3b6a6" stroke-width="3" stroke-linecap="round"/>
        <path d="M-6 15c4-20 43-25 56-4 9 16 6 35-10 42-17 7-43 2-48-12-4-10-3-18 2-26Z" fill="#faf4e5" stroke="#627b6c" stroke-width="1.8"/>
        <g class="reader-eyes" fill="#4f665b"><ellipse cx="9" cy="25" rx="2" ry="3"/><ellipse cx="32" cy="25" rx="2" ry="3"/></g>
        <path d="m19 31 3 2 3-2m-3 2v4m0 0c-3 3-5 3-7 1m7-1c3 3 5 3 7 1" stroke="#7b8170" stroke-width="1.2" stroke-linecap="round"/>
        <ellipse cx="0" cy="35" rx="5" ry="2.5" fill="#e0bdad" opacity=".6"/><ellipse cx="41" cy="34" rx="5" ry="2.5" fill="#e0bdad" opacity=".6"/>
      </g>
      <g class="reader-book">
        <path d="m-14 67 35 9 35-12-4 33-30 13-31-12Z" fill="#fbf5e3" stroke="#648074" stroke-width="1.7" stroke-linejoin="round"/>
        <path d="m21 76 1 34M-6 77l17 5m-16 2 16 5m20-7 15-5m-15 12 15-5" stroke="#bdc4ae" stroke-width="1.3" stroke-linecap="round"/>
        <path class="book-page" d="m23 77 28-13-2 27-26 17Z" fill="#fffaf0" stroke="#9da68d" stroke-width="1"/>
        <path d="M-10 78c-13-7-16 7-4 10m63-11c13-7 18 5 5 10" fill="#faf4e5" stroke="#627b6c" stroke-width="1.7" stroke-linecap="round"/>
      </g>
    </g>
  </g>

  <g transform="translate(391 377)"><path d="m0 20 31-3 3 12-33 2Z" fill="#90a8c1" stroke="#647f74" stroke-width="1.3"/><path d="m3 9 29-3 2 11-31 3Z" fill="#d7bf81" stroke="#798371" stroke-width="1.3"/><path d="m4 9 26-2m-26 17 25-2" stroke="#f8f1dd" stroke-width="3"/></g>
  <g transform="translate(603 383)"><path d="M0 4h16v16c-6 4-11 4-16 0Z" fill="#f5f1e2" stroke="#7a9282" stroke-width="1.5"/><path d="M17 7c13-2 13 13 0 11" stroke="#7a9282" stroke-width="1.5"/><g class="garden-steam" stroke="#a5b7a5" stroke-width="1.4" stroke-linecap="round"><path d="M5-2c-7-6 6-9 0-16m8 17c-6-5 4-8 0-13"/></g></g>
  </g>

  <g class="garden-tent" transform="translate(883 340)"><path d="M0 57 35 17 83 51l-6 22H3Z" fill="#d7ccab" stroke="#6a8776" stroke-width="1.8"/><path d="m35 17 4 57m-4-57 48 34-44 23M13 58l18-25 4 33" stroke="#a7a786" stroke-width="1.2"/><path d="m40 38 19 26-20 10Z" fill="#809a85"/><path d="M30 17 33 7m2 10 5-10" stroke="#6a8776" stroke-width="1.6"/></g>
  <g class="garden-butterfly" transform="translate(815 330)"><path d="M0 0c-20-21-27-4-13 4-18 2-8 18 5 10L0 6" fill="#d6bd7a" stroke="#8d9877" stroke-width="1"/><path d="M0 0C19-22 27-5 13 4c18 1 9 18-4 10L0 6" fill="#eee1aa" stroke="#8d9877" stroke-width="1"/><path d="m0-1 1 14m-1-15-4-6m4 6 4-6" stroke="#7b8e70" stroke-width="1.5" stroke-linecap="round"/></g>

  <path d="M1050 446c17-9 39-9 54-2m102-1c8-4 20-4 29-1M207 434c14-5 28-5 39-1" stroke="#abc2a7" stroke-width="2" stroke-linecap="round"/>
  <rect width="1440" height="540" fill="url(#garden-paper)" pointer-events="none"/>
</svg>`;

document.querySelectorAll('[data-garden-scene]').forEach((host, index) => {
  // SVG fragment IDs must stay unique when the same scene appears on several screens.
  host.innerHTML = gardenArt.replaceAll('id="garden-', `id="scene-${index}-`).replaceAll('href="#garden-', `href="#scene-${index}-`).replaceAll('url(#garden-', `url(#scene-${index}-`);
  if (host.hasAttribute('data-garden-compact')) {
    host.querySelector('svg').setAttribute('preserveAspectRatio', 'xMaxYMax meet');
  }
});

const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
let manuallyPaused = false;
function updateMotion() {
  const paused = manuallyPaused || motionPreference.matches;
  document.body.classList.toggle('motion-paused', paused);
  document.querySelectorAll('[data-motion-toggle]').forEach(button => {
    button.setAttribute('aria-pressed', String(paused));
    button.disabled = motionPreference.matches;
    button.setAttribute('aria-label', motionPreference.matches ? 'Animasi nonaktif mengikuti pengaturan perangkat' : paused ? 'Putar animasi' : 'Jeda animasi');
    const icon = button.querySelector('[data-icon]');
    icon.dataset.icon = paused ? 'play' : 'pause';
    renderIcons(button);
  });
}
document.querySelectorAll('[data-motion-toggle]').forEach(button => button.addEventListener('click', () => {
  manuallyPaused = !manuallyPaused;
  updateMotion();
}));
motionPreference.addEventListener('change', updateMotion);
updateMotion();

// Offscreen or background scenes do not need animation work.
const sceneObserver = new IntersectionObserver(entries => {
  for (const entry of entries) entry.target.classList.toggle('scene-offscreen', !entry.isIntersecting);
}, { threshold: 0.01 });
document.querySelectorAll('[data-garden-scene], [data-motion-scene]').forEach(scene => sceneObserver.observe(scene));
document.addEventListener('visibilitychange', () => document.body.classList.toggle('page-inactive', document.hidden));
