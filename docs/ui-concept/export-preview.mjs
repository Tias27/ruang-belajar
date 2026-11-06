import { readFile, writeFile, mkdtemp } from 'node:fs/promises';
import { spawn } from 'node:child_process';
import os from 'node:os';
import { fileURLToPath, pathToFileURL } from 'node:url';
import path from 'node:path';

// Uses a separate headless Chrome session; never connects to a personal browser profile.
const directory = path.dirname(fileURLToPath(import.meta.url));
const embeddedFonts = (await Promise.all([400, 500, 600, 700].map(async weight => {
  const bytes = await readFile(path.join(directory, 'fonts', `plus-jakarta-sans-${weight}.ttf`));
  return `@font-face{font-family:'Plus Jakarta Sans';font-weight:${weight};src:url(data:font/ttf;base64,${bytes.toString('base64')}) format('truetype')}`;
}))).join('\n');
const profile = await mkdtemp(path.join(os.tmpdir(), 'ruangbelajar-ui-preview-'));
const browser = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
  '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
  '--disable-background-networking', '--remote-debugging-port=9334', `--user-data-dir=${profile}`, 'about:blank',
], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
let browserErrors = '';
browser.stderr.on('data', chunk => { browserErrors += chunk; });
let target;
for (let attempt = 0; attempt < 30; attempt++) {
  try { target = (await (await fetch('http://127.0.0.1:9334/json/list')).json()).find(item => item.type === 'page'); } catch {}
  if (target) break;
  await new Promise(resolve => setTimeout(resolve, 300));
}
if (!target) { browser.kill(); throw new Error(`Headless Chrome did not start: ${browserErrors}`); }
const socket = new WebSocket(target.webSocketDebuggerUrl);
await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
let sequence = 0;
const pending = new Map();
socket.onclose = event => {
  for (const call of pending.values()) { clearTimeout(call.timer); call.reject(new Error(`Chrome socket closed: ${event.code} ${event.reason}; ${browserErrors}`)); }
  pending.clear();
};
socket.onmessage = event => {
  const message = JSON.parse(event.data);
  if (!message.id) return;
  const call = pending.get(message.id);
  if (!call) return;
  pending.delete(message.id);
  clearTimeout(call.timer);
  if (message.error) call.reject(new Error(JSON.stringify(message.error)));
  else call.resolve(message.result);
};
const cdp = (method, params = {}) => new Promise((resolve, reject) => {
  const id = ++sequence;
  const timer = setTimeout(() => { pending.delete(id); reject(new Error(`CDP timeout: ${method}; ${browserErrors}`)); }, 15000);
  pending.set(id, { resolve, reject, timer });
  socket.send(JSON.stringify({ id, method, params }));
});
const evaluate = async expression => {
  const result = await cdp('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
  if (result.exceptionDetails) throw new Error(JSON.stringify(result.exceptionDetails));
  return result.result.value;
};

// Export ordinary SVG shapes and text, not a screenshot or foreignObject.
function vectorExport(fullPage = false) {
  const width = innerWidth;
  const height = innerWidth < 600 && !fullPage ? innerHeight : Math.max(innerHeight, document.documentElement.scrollHeight);
  const xml = value => String(value).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&apos;' })[c]);
  const n = value => Number(value.toFixed(2));
  const parts = [`<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">`, '<title>Ruang Belajar - konsep UI</title>', `<defs><clipPath id="viewport"><rect width="${width}" height="${height}"/></clipPath></defs><g clip-path="url(#viewport)">`];
  const visibleColor = color => color && color !== 'transparent' && color !== 'rgba(0, 0, 0, 0)';
  const fontSize = value => parseFloat(value) || 12;
  let clipSequence = 0;

  function textNode(node, style) {
    if (!node.textContent.trim()) return;
    const content = node.textContent;
    const lines = [];
    let line = null;
    for (let i = 0; i < content.length; i++) {
      const range = document.createRange();
      range.setStart(node, i);
      range.setEnd(node, i + 1);
      const rect = range.getBoundingClientRect();
      if (!rect.width || !rect.height) continue;
      if (!line || Math.abs(line.top - rect.top) > 3) {
        line = { left: rect.left, top: rect.top, height: rect.height, text: content[i] };
        lines.push(line);
      } else line.text += content[i];
    }
    for (const line of lines) {
      const size = fontSize(style.fontSize);
      const text = style.textTransform === 'uppercase' ? line.text.toUpperCase() : line.text;
      const baseline = line.top + (line.height - size) / 2 + size * .81 + scrollY;
      parts.push(`<text x="${n(line.left)}" y="${n(baseline)}" fill="${style.color}" font-family="${xml(style.fontFamily)}" font-size="${size}" font-weight="${style.fontWeight}" font-style="${style.fontStyle}" letter-spacing="${style.letterSpacing === 'normal' ? 0 : parseFloat(style.letterSpacing)}" xml:space="preserve">${xml(text)}</text>`);
    }
  }

  function walk(element) {
    const style = getComputedStyle(element);
    if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return;
    if (element.matches('script,style,link,meta,title,dialog:not([open]),.sr-only,.skip-link')) return;
    const rect = element.getBoundingClientRect();
    const x = n(rect.left), y = n(rect.top + scrollY), w = n(rect.width), h = n(rect.height);
    if (!w || !h || y >= height || x >= width || x + w <= 0 || y + h <= 0) return;
    parts.push(`<g data-name="${xml(element.id || element.className?.baseVal || element.className || element.tagName)}"${Number(style.opacity) < 1 ? ` opacity="${style.opacity}"` : ''}>`);
    const radius = parseFloat(style.borderTopLeftRadius) || 0;
    if (visibleColor(style.backgroundColor)) parts.push(`<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${radius}" fill="${style.backgroundColor}"/>`);
    const borders = ['Top', 'Right', 'Bottom', 'Left'].map(side => ({ width: parseFloat(style[`border${side}Width`]), color: style[`border${side}Color`], style: style[`border${side}Style`] }));
    if (borders.every(border => border.width === borders[0].width && border.color === borders[0].color) && borders[0].width) {
      const b = borders[0];
      parts.push(`<rect x="${n(x + b.width / 2)}" y="${n(y + b.width / 2)}" width="${n(w - b.width)}" height="${n(h - b.width)}" rx="${radius}" fill="none" stroke="${b.color}" stroke-width="${b.width}"/>`);
    } else {
      const coords = [[x,y,x+w,y], [x+w,y,x+w,y+h], [x,y+h,x+w,y+h], [x,y,x,y+h]];
      borders.forEach((b,i) => { if (b.width && visibleColor(b.color)) parts.push(`<path d="M${coords[i][0]} ${coords[i][1]}L${coords[i][2]} ${coords[i][3]}" stroke="${b.color}" stroke-width="${b.width}"/>`); });
    }
    const clipsChildren = ['hidden', 'clip'].includes(style.overflowX) || ['hidden', 'clip'].includes(style.overflowY);
    if (clipsChildren) {
      const clipId = `export-clip-${++clipSequence}`;
      parts.push(`<defs><clipPath id="${clipId}"><rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${radius}"/></clipPath></defs><g clip-path="url(#${clipId})">`);
    }
    if (element.tagName.toLowerCase() === 'svg') {
      const clone = element.cloneNode(true);
      const originals = [element, ...element.querySelectorAll('*')];
      const copies = [clone, ...clone.querySelectorAll('*')];
      originals.forEach((original, index) => {
        const computed = getComputedStyle(original);
        for (const prop of ['fill','stroke','stroke-width','stroke-linecap','stroke-linejoin','opacity']) copies[index].setAttribute(prop, computed.getPropertyValue(prop));
        if (computed.display === 'none') copies[index].setAttribute('display', 'none');
        if (computed.visibility === 'hidden') copies[index].setAttribute('visibility', 'hidden');
        // Keep responsive SVG group positions; root positioning is already in its bounds.
        if (index > 0 && computed.transform !== 'none') copies[index].setAttribute('transform', computed.transform);
        copies[index].removeAttribute('class');
      });
      clone.setAttribute('x', x); clone.setAttribute('y', y); clone.setAttribute('width', w); clone.setAttribute('height', h);
      parts.push(new XMLSerializer().serializeToString(clone));
    } else if (element instanceof HTMLTextAreaElement || element instanceof HTMLInputElement) {
      if (element.type !== 'checkbox' && element.type !== 'file') {
        const paddingX = parseFloat(style.paddingLeft) || 0;
        const paddingY = parseFloat(style.paddingTop) || 0;
        const size = fontSize(style.fontSize);
        const words = (element.value || element.placeholder || '').split(/\s+/);
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        ctx.font = `${style.fontWeight} ${size}px ${style.fontFamily}`;
        let lines = [''];
        for (const word of words) {
          const last = lines.length - 1;
          const trial = `${lines[last]}${lines[last] ? ' ' : ''}${word}`;
          if (ctx.measureText(trial).width > w - paddingX * 2 - 4 && lines[last]) lines.push(word);
          else lines[last] = trial;
        }
        const lineHeight = parseFloat(style.lineHeight) || size * 1.55;
        lines.forEach((line, i) => parts.push(`<text x="${n(x + paddingX)}" y="${n(y + paddingY + size + i * lineHeight)}" fill="${style.color}" font-family="${xml(style.fontFamily)}" font-size="${size}">${xml(line)}</text>`));
      }
    } else {
      // Positioned overlays, including the landing navigation, paint above the scene.
      const stackLevel = node => node.nodeType === Node.ELEMENT_NODE ? Number.parseInt(getComputedStyle(node).zIndex, 10) || 0 : 0;
      const children = [...element.childNodes].sort((a, b) => stackLevel(a) - stackLevel(b));
      for (const child of children) {
        if (child.nodeType === Node.TEXT_NODE) textNode(child, style);
        else if (child.nodeType === Node.ELEMENT_NODE) walk(child);
      }
    }
    if (clipsChildren) parts.push('</g>');
    parts.push('</g>');
  }
  walk(document.body);
  parts.push('</g></svg>');
  return parts.join('\n');
}

try {
  await cdp('Page.enable');
  await cdp('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
  const captures = [
    { name: '01-beranda-desktop', hash: 'home', width: 1440, height: 1000 },
    { name: '02-materi-desktop', hash: 'library', width: 1440, height: 1060 },
    { name: '03-belajar-desktop', hash: 'study', width: 1440, height: 1110 },
    { name: '04-beranda-mobile', hash: 'home', width: 390, height: 844 },
    { name: '05-belajar-mobile', hash: 'study', width: 390, height: 844 },
    { name: '06-halaman-awal-desktop', hash: 'landing', width: 1440, height: 1000, guest: true },
    { name: '07-login-desktop', hash: 'login', width: 1440, height: 1000, guest: true },
    { name: '08-daftar-desktop', hash: 'register', width: 1440, height: 1080, guest: true },
    { name: '09-halaman-awal-mobile', hash: 'landing', width: 390, height: 844, guest: true },
    { name: '10-login-mobile', hash: 'login', width: 390, height: 844, guest: true, fullPage: true },
    { name: '11-daftar-mobile', hash: 'register', width: 390, height: 844, guest: true, fullPage: true },
    { name: '12-riwayat-desktop', hash: 'history', width: 1440, height: 1000 },
    { name: '13-belajar-bareng-desktop', hash: 'rooms', width: 1440, height: 1000 },
    { name: '14-tanya-ai-desktop', hash: 'study?tab=chat', width: 1440, height: 1000 },
    { name: '15-latihan-soal-desktop', hash: 'study?tab=quiz', width: 1440, height: 1080 },
    { name: '16-kartu-belajar-desktop', hash: 'study?tab=cards', width: 1440, height: 1000 },
    { name: '17-materi-mobile', hash: 'library', width: 390, height: 844, fullPage: true },
    { name: '18-panduan-desktop', hash: 'guide', width: 1440, height: 1000 },
    { name: '19-belajar-bareng-mobile', hash: 'rooms', width: 390, height: 844, fullPage: true },
  ];
  for (const item of captures.filter(item => (!process.argv.includes('--guest') || item.guest) && (!process.argv.includes('--workspace') || !item.guest) && (!process.argv.includes('--rooms') || item.hash === 'rooms'))) {
    await cdp('Emulation.setDeviceMetricsOverride', { width: item.width, height: item.height, deviceScaleFactor: 1, mobile: item.width < 600 });
    await cdp('Page.navigate', { url: `${pathToFileURL(path.join(directory, 'index.html')).href}#${item.hash}` });
    await new Promise(resolve => setTimeout(resolve, 600));
    await evaluate('document.fonts.ready.then(() => true)');
    const metrics = await evaluate('({ width:innerWidth, documentWidth:document.documentElement.scrollWidth, height:document.documentElement.scrollHeight, title:document.title })');
    if (metrics.documentWidth > metrics.width) throw new Error(`Horizontal overflow: ${item.name}`);
    // Place fixed mobile navigation at the bottom of a full-page review image.
    if (item.fullPage && item.width < 600) {
      await evaluate(`(() => { const nav = document.querySelector('.mobile-nav'); if (nav && getComputedStyle(nav).display !== 'none') { nav.style.position = 'absolute'; nav.style.top = (document.documentElement.scrollHeight - nav.offsetHeight) + 'px'; nav.style.bottom = 'auto'; } })()`);
    }
    const screenshot = await cdp('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width: item.width, height: item.width < 600 && !item.fullPage ? item.height : metrics.height, scale: 1 } });
    await writeFile(path.join(directory, `${item.name}.png`), Buffer.from(screenshot.data, 'base64'));
    // Flatten only decorative rotations for SVG; all interface text and shapes stay separate.
    await evaluate(`(() => { const style = document.createElement('style'); style.textContent = '.paper-front,.paper-back,.example-source,.story-notebook,.register-paper{transform:none!important}.screen{animation:none!important}'; document.head.append(style); window.scrollTo(0,0); })()`);
    const svg = await evaluate(`(${vectorExport.toString()})(${Boolean(item.fullPage)})`);
    await writeFile(path.join(directory, `${item.name}.svg`), svg.replace('<defs>', `<defs><style>${embeddedFonts}</style>`));
    console.log(JSON.stringify({ exported: item.name, ...metrics }));
  }
} finally {
  await cdp('Browser.close').catch(() => {});
  socket.close();
  browser.kill();
}
