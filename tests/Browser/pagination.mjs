import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import { spawn, spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const project = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const testingRoot = path.join(project, 'storage/framework/testing');
await fs.mkdir(testingRoot, { recursive: true });
const fixture = await fs.mkdtemp(path.join(testingRoot, 'pagination-'));
const env = { ...process.env, PAGINATION_FIXTURE_DIR: fixture, CHAT_MATERIAL_FIXTURE: process.argv.includes('--chat-materials') || process.argv.includes('--attachment-preview') ? '1' : '', WEB_CHAT_FIXTURE: process.argv.includes('--web-chat') ? '1' : '', ATTACHMENT_FIXTURE: process.argv.includes('--attachment-preview') ? '1' : '', SUMMARY_FIXTURE: process.argv.includes('--summary-wait') ? '1' : '' };
const phpArgs = ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3'];
const fixtureScript = 'tests/Browser/pagination-fixture.php';
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
let server, browser, socket;
const errors = [];
const checks = [];

try {
    const setup = spawnSync('php', [...phpArgs, fixtureScript], { cwd: project, env, encoding: 'utf8', windowsHide: true });
    assert.equal(setup.status, 0, setup.stdout + setup.stderr);
    server = spawn('php', [...phpArgs, '-S', '127.0.0.1:8125', '-t', 'public', fixtureScript], { cwd: project, env, windowsHide: true, stdio: 'ignore' });
    browser = spawn(process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', [
        '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--disable-background-networking',
        '--remote-debugging-port=9355', `--user-data-dir=${fixture}/chrome`, 'about:blank',
    ], { windowsHide: true, stdio: 'ignore' });
    let target;
    for (let i = 0; i < 40; i++) {
        try { target = (await (await fetch('http://127.0.0.1:9355/json/list')).json()).find(item => item.type === 'page'); } catch {}
        if (target) break;
        await delay(150);
    }
    assert.ok(target, 'Chrome starts');
    socket = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
    let sequence = 0;
    const pending = new Map();
    socket.onmessage = event => {
        const message = JSON.parse(event.data);
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.exception?.description || message.params.exceptionDetails.text);
        if (!message.id) return;
        const call = pending.get(message.id);
        if (!call) return;
        pending.delete(message.id);
        clearTimeout(call.timer);
        message.error ? call.reject(new Error(JSON.stringify(message.error))) : call.resolve(message.result);
    };
    const cdp = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++sequence;
        const timer = setTimeout(() => { pending.delete(id); reject(new Error(`Timeout: ${method}`)); }, 15000);
        pending.set(id, { resolve, reject, timer });
        socket.send(JSON.stringify({ id, method, params }));
    });
    const evaluate = async expression => {
        const result = await cdp('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        if (result.exceptionDetails) throw new Error(JSON.stringify(result.exceptionDetails));
        return result.result.value;
    };
    async function until(expression) {
        for (let i = 0; i < 100; i++) {
            if (await evaluate(expression)) return;
            await delay(100);
        }
        throw new Error(`Condition timed out: ${expression}`);
    }
    async function go(url) {
        await cdp('Page.navigate', { url: 'http://127.0.0.1:8125' + url });
        await until(`location.href === ${JSON.stringify('http://127.0.0.1:8125' + url)} && document.readyState === 'complete' && !!window.Alpine`);
        await evaluate("document.fonts.ready.then(() => { window.pageIdentity = 'same-document'; window.originalNavigation = performance.timeOrigin; return true; })");
    }
    async function check(name, expression) {
        assert.ok(await evaluate(expression), name);
        checks.push(name);
    }
    async function clickPage(name, direction, page) {
        await evaluate(`document.querySelector('[data-pagination-region="${name}"] .compact-pagination a[rel="${direction}"]').click()`);
        await until(`new URL(location.href).searchParams.get('${name}') === '${page}' && !document.querySelector('[aria-busy="true"]')`);
        await check(`${name} page ${page} without reload`, "window.pageIdentity === 'same-document' && performance.timeOrigin === window.originalNavigation");
    }

    await cdp('Page.enable'); await cdp('Runtime.enable'); await cdp('Network.enable');
    if (process.argv.includes('--web-chat')) {
        const { checkWebChat } = await import('./web-chat-checks.mjs');
        await checkWebChat({ cdp, evaluate, until, go, check, testingRoot });
    } else if (process.argv.includes('--attachment-preview')) {
        const { checkAttachmentPreview } = await import('./chat-attachment-checks.mjs');
        await checkAttachmentPreview({ cdp, evaluate, until, go, check, testingRoot });
    } else if (process.argv.includes('--summary-wait')) {
        const { checkSummaryWait } = await import('./summary-wait-checks.mjs');
        await checkSummaryWait({ cdp, evaluate, until, go, check, fixture });
    } else if (process.argv.includes('--flashcard-deck')) {
        const { checkFlashcardDeck } = await import('./flashcard-deck-checks.mjs');
        await checkFlashcardDeck({ cdp, evaluate, until, go, check, fixture });
    } else if (process.argv.includes('--library-layout')) {
        const { checkLibraryLayout } = await import('./library-layout-checks.mjs');
        await checkLibraryLayout({ cdp, evaluate, until, go, check, fixture, testingRoot, clickPage });
    } else if (process.argv.includes('--quiz-dialog')) {
        const { checkQuizDialog } = await import('./quiz-dialog-checks.mjs');
        await checkQuizDialog({ cdp, evaluate, until, go, check, fixture, testingRoot });
    } else if (process.argv.includes('--chat-rich')) {
        const { checkChatRich } = await import('./chat-rich-checks.mjs');
        await checkChatRich({ cdp, evaluate, until, go, check, testingRoot });
    } else if (process.argv.includes('--chat-materials')) {
        const { checkChatMaterials } = await import('./chat-material-checks.mjs');
        await checkChatMaterials({ cdp, evaluate, until, go, check, fixture, testingRoot });
    } else if (process.argv.includes('--chat-layout')) {
        const { checkChatLayout } = await import('./chat-layout-checks.mjs');
        await checkChatLayout({ cdp, evaluate, until, go, check, fixture, testingRoot });
    } else {
    await cdp('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
    await go('/dashboard');
    await evaluate("window.originalHero = document.querySelector('.continue-panel'); window.originalReview = document.querySelector('.review-note'); document.querySelector('.materials-section').scrollIntoView(); window.originalScroll = scrollY;");
    await clickPage('materials_page', 'next', 2);
    await check('hero and reminder remain intact', "originalHero === document.querySelector('.continue-panel') && originalReview === document.querySelector('.review-note')");
    await check('scroll position retained', 'Math.abs(scrollY - originalScroll) < 2');
    await clickPage('activity_page', 'next', 2);
    await check('other page remains selected', "new URL(location.href).searchParams.get('materials_page') === '2'");
    await evaluate('history.back()');
    await until("!new URL(location.href).searchParams.has('activity_page') && !document.querySelector('[aria-busy]') && document.querySelector('[data-pagination-region=activity_page] .pagination-position').textContent.trim() === '1 / 3'");
    await check('browser Back without reload', "window.pageIdentity === 'same-document'");
    await evaluate('history.forward()');
    await until("document.querySelector('[data-pagination-region=activity_page] .pagination-position').textContent.trim() === '2 / 3' && !document.querySelector('[aria-busy]')");
    await check('browser Forward without reload', "window.pageIdentity === 'same-document'");

    await go('/documents');
    await evaluate("window.nativeFetch = window.fetch; window.fetch = async (...args) => { if (new URL(args[0]).searchParams.has('folders_page')) await new Promise(resolve => setTimeout(resolve, 500)); return nativeFetch(...args); };");
    await evaluate("document.querySelector('[data-pagination-region=folders_page] a[rel=next]').click(); document.querySelector('[data-pagination-region=page] a[rel=next]').click();");
    await until("new URL(location.href).searchParams.get('folders_page') === '2' && new URL(location.href).searchParams.get('page') === '2' && !document.querySelector('[aria-busy]')");
    await check('concurrent lists keep both page selections', "document.querySelector('[data-pagination-region=folders_page] .pagination-position').textContent.trim() === '2 / 3' && document.querySelector('[data-pagination-region=page] .pagination-position').textContent.trim() === '2 / 3'");
    await check('replaced material menu still works', "document.querySelector('.live-row-menu summary').click(); document.querySelector('.live-row-menu').open");
    await evaluate("document.querySelector('.live-row-menu-close').click()");

    await go('/riwayat?q=Bahas');
    await evaluate("document.querySelector('.compact-list-toolbar button[type=button]').click()");
    await clickPage('page', 'next', 2);
    await check('selection resets for new rows', "document.querySelectorAll('.history-select input:checked').length === 0");
    await evaluate("document.querySelector('.compact-list-toolbar button[type=button]').click()");
    await until("document.querySelectorAll('.history-select input:checked').length === 5");
    await check('selection uses current page only', "Alpine.$data(document.querySelector('#conversation-list')).selected.every(id => [...document.querySelectorAll('.history-select input')].some(input => input.value === id))");
    await check('search remains in URL', "new URL(location.href).searchParams.get('q') === 'Bahas'");
    await evaluate("window.historyPagerTop = document.querySelector('.compact-pagination').getBoundingClientRect().top + scrollY");
    await clickPage('page', 'next', 3);
    await check('last page has remaining rows', "document.querySelectorAll('.compact-history-row').length === 2");
    await check('short history page keeps pagination position', "Math.abs(document.querySelector('.compact-pagination').getBoundingClientRect().top + scrollY - historyPagerTop) < 2");
    await clickPage('page', 'prev', 2);
    await check('previous page does not accumulate blank space', "Math.abs(document.querySelector('.compact-pagination').getBoundingClientRect().top + scrollY - historyPagerTop) < 2");

    await go('/documents?page=2&folders_page=2');
    await evaluate("window.filePagerTop = document.querySelector('[data-pagination-region=page] .compact-pagination').getBoundingClientRect().top + scrollY; window.folderPagerTop = document.querySelector('[data-pagination-region=folders_page] .compact-pagination').getBoundingClientRect().top + scrollY;");
    await clickPage('page', 'next', 3);
    await check('short file list keeps controls in place', "Math.abs(document.querySelector('[data-pagination-region=page] .compact-pagination').getBoundingClientRect().top + scrollY - filePagerTop) < 2");
    await clickPage('folders_page', 'next', 3);
    await check('short folder list keeps both lists in place', "Math.abs(document.querySelector('[data-pagination-region=folders_page] .compact-pagination').getBoundingClientRect().top + scrollY - folderPagerTop) < 2 && Math.abs(document.querySelector('[data-pagination-region=page] .compact-pagination').getBoundingClientRect().top + scrollY - filePagerTop) < 2");

    const data = JSON.parse(await fs.readFile(path.join(fixture, 'data.json'), 'utf8'));
    await go(data.document);
    await evaluate("document.querySelector('textarea[name=content]').value = 'Catatan belum disimpan'; window.originalNote = document.querySelector('textarea[name=content]');");
    await clickPage('summary_page', 'next', 2);
    await check('unsaved note remains intact', "originalNote === document.querySelector('textarea[name=content]') && originalNote.value === 'Catatan belum disimpan'");
    await go('/study-rooms');
    await clickPage('materials_page', 'next', 2);
    await check('new room material icons render', "!!document.querySelector('.room-material svg.icon')");
    await evaluate("document.querySelector('[data-room-upload-open]').click()");
    await check('existing upload dialog still works', "document.querySelector('#room-upload-dialog').open");
    await evaluate("document.querySelector('#room-upload-dialog').close()");

    await go('/dashboard');
    await evaluate("window.nativeFetch = window.fetch; window.fetch = () => Promise.reject(new TypeError('Offline')); window.oldRows = document.querySelector('.material-list').innerHTML;");
    await evaluate("document.querySelector('.materials-section a[rel=next]').click()");
    await until("!!document.querySelector('[data-pagination-error]') && !document.querySelector('[aria-busy]')");
    await check('failure retains rows and URL without reload', "document.querySelector('.material-list').innerHTML === oldRows && !new URL(location.href).searchParams.has('materials_page') && window.pageIdentity === 'same-document'");
    await evaluate("window.fetch = window.nativeFetch; document.querySelector('[data-pagination-error] button').click()");
    await until("new URL(location.href).searchParams.get('materials_page') === '2' && !document.querySelector('[aria-busy]')");
    await check('retry recovers without reload', "!document.querySelector('[data-pagination-error]') && window.pageIdentity === 'same-document'");
    await evaluate("window.nativeFetch = window.fetch; let calls = 0; window.fetch = async (...args) => { const call = ++calls; const response = await nativeFetch(...args); if (call === 1) await new Promise(resolve => setTimeout(resolve, 500)); return response; }; document.querySelector('.materials-section a[rel=next]').click(); document.querySelector('.materials-section a[rel=prev]').click();");
    await until("new URL(location.href).searchParams.get('materials_page') === '1' && !document.querySelector('[aria-busy]')");
    await delay(600);
    await check('stale response cannot replace latest choice', "document.querySelector('.materials-section .pagination-position').textContent.trim() === '1 / 12'");

    await cdp('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });
    await go('/dashboard');
    await evaluate("document.querySelector('.materials-section a[rel=next]').focus(); window.originalScroll = scrollY;");
    await clickPage('materials_page', 'next', 2);
    await check('desktop pagination keeps keyboard focus', "document.activeElement.matches('.materials-section a[rel=next]')");
    await check('desktop scroll stays in place', 'Math.abs(scrollY - originalScroll) < 2');
    }
    assert.deepEqual(errors, []);
    console.log(`PASS: ${checks.length} browser checks; no JavaScript errors`);
} finally {
    socket?.close();
    browser?.kill();
    server?.kill();
    await delay(500);
    const relative = path.relative(testingRoot, fixture);
    if (!relative.startsWith('..') && !path.isAbsolute(relative) && relative.startsWith('pagination-')) {
        await fs.rm(fixture, { recursive: true, force: true, maxRetries: 10, retryDelay: 200 });
    }
}
