import fs from 'node:fs/promises';
import path from 'node:path';

export async function checkChatLayout({ cdp, evaluate, until, go, check, fixture, testingRoot }) {
    const data = JSON.parse(await fs.readFile(path.join(fixture, 'data.json'), 'utf8'));
    for (const [width, height] of [[1440, 900], [1164, 540], [820, 600], [390, 844], [360, 640], [390, 480]]) {
        await cdp('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 760 });
        await go(data.chat);
        await until("!!document.querySelector('.live-chat-welcome')");
        await evaluate(`window.chat = Alpine.$data(document.querySelector('.live-ai-chat'));
            window.rect = selector => document.querySelector(selector).getBoundingClientRect();`);
        await check(`${width}x${height}: empty chat starts at top`, "document.querySelector('[x-ref=messages]').scrollTop === 0 && rect('.live-chat-welcome h2').top >= rect('[x-ref=messages]').top");
        await check(`${width}x${height}: composer does not cover messages`, "rect('[x-ref=messages]').bottom <= rect('.live-chat-composer').top + 1 && rect('[x-ref=messages]').height > 100");
        await check(`${width}x${height}: composer remains on screen`, "rect('.live-chat-composer').bottom <= innerHeight && rect('.live-chat-composer').top > 0 && (getComputedStyle(document.querySelector('.mobile-nav')).display === 'none' || rect('.live-chat-composer').bottom <= rect('.mobile-nav').top + 1)");
        await check(`${width}x${height}: no horizontal page overflow`, 'document.documentElement.scrollWidth <= innerWidth');
        await check(`${width}x${height}: feature bar absent on result page`, "!document.querySelector('.live-study-navigation') && !!document.querySelector('a[aria-label=\"Kembali ke materi\"]')");
        if (height >= 600 || width > 700) {
            await check(`${width}x${height}: all starter prompts visible`, "rect('.live-chat-prompts').bottom <= rect('[x-ref=messages]').bottom + 1");
        }
        if (width === 1440 || height === 844 || width === 1164) {
            const capture = await cdp('Page.captureScreenshot', { format: 'png' });
            await fs.writeFile(path.join(testingRoot, `chat-layout-${width}.png`), Buffer.from(capture.data, 'base64'));
        }
        await evaluate("document.querySelector('.live-chat-prompt:last-child').scrollIntoView({ block: 'nearest', behavior: 'instant' })");
        await check(`${width}x${height}: last prompt reachable`, "rect('.live-chat-prompt:last-child').bottom <= rect('[x-ref=messages]').bottom + 1");
        await evaluate("chat.title = 'Judul materi panjang '.repeat(30)");
        await check(`${width}x${height}: long title stays within header`, "document.documentElement.scrollWidth <= innerWidth && rect('.live-chat-header').height < 90");
    }

    await cdp('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
    await go(data.chat);
    await evaluate("window.chat = Alpine.$data(document.querySelector('.live-ai-chat')); window.rect = selector => document.querySelector(selector).getBoundingClientRect();");
    await evaluate(`chat.messages = Array.from({length: 20}, (_, i) => ({id: i, role: i % 2 ? 'assistant' : 'user', content: 'Isi percakapan untuk menguji scroll. '.repeat(8)})); chat.scrollToBottom(true);`);
    await until("document.querySelector('[x-ref=messages]').scrollHeight - document.querySelector('[x-ref=messages]').scrollTop - document.querySelector('[x-ref=messages]').clientHeight < 2");
    await check('last message is above composer', "rect('[x-ref=messages] > div:last-child').bottom <= rect('.live-chat-composer').top + 1");
    await evaluate(`chat.imagePreview = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        const input = document.querySelector('textarea[name=question]'); input.value = 'Pertanyaan panjang\\n'.repeat(20); input.dispatchEvent(new Event('input', { bubbles: true }));`);
    await check('long draft and image preview keep composer visible', "rect('.live-chat-composer').bottom <= rect('.mobile-nav').top + 1 && rect('[x-ref=messages]').height > 100");
    await check('long draft can scroll inside textarea', "getComputedStyle(document.querySelector('textarea[name=question]')).overflowY === 'auto' && document.querySelector('textarea[name=question]').scrollHeight > document.querySelector('textarea[name=question]').clientHeight");
    await evaluate("chat.clearImage(); chat.question = 'Baris pertama'; document.querySelector('textarea[name=question]').focus()");
    await evaluate('Alpine.nextTick()');
    await cdp('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Enter', code: 'Enter', text: '\r', unmodifiedText: '\r', windowsVirtualKeyCode: 13, modifiers: 8 });
    await cdp('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13 });
    await check('Shift+Enter inserts newline', "document.querySelector('textarea[name=question]').value.includes('\\n') && !chat.sending");

    await go(data.chat);
    await check('send button shows a single arrow while idle', "document.querySelectorAll('.live-chat-input button[type=submit] svg').length === 1 && !document.querySelector('.live-chat-input button[type=submit] svg.animate-spin')");
    await evaluate(`window.chat = Alpine.$data(document.querySelector('.live-ai-chat'));
        window.fetch = async () => new Response(new ReadableStream({start(controller) {
            window.replyStream = controller;
            controller.enqueue(new TextEncoder().encode('data: ' + JSON.stringify({chunk: 'Jawaban '}) + '\\n\\n'));
        }}), {headers: {'Content-Type': 'text/event-stream'}});
        document.querySelector('.live-chat-prompt').click();`);
    await until("chat.messages.length === 2 && chat.messages[1].content === 'Jawaban '");
    await check('partial reply appears before stream completes', "chat.sending && chat.messages[1].content === 'Jawaban '");
    await check('send button shows only its spinner while sending', "document.querySelectorAll('.live-chat-input button[type=submit] svg').length === 1 && document.querySelector('.live-chat-input button[type=submit] svg.animate-spin')");
    await evaluate(`replyStream.enqueue(new TextEncoder().encode('data: ' + JSON.stringify({chunk: 'uji'}) + '\\n\\n' + 'data: ' + JSON.stringify({done: true, title: 'Percakapan uji', message: {id: 99, content: 'Jawaban uji', metadata: null}}) + '\\n\\n')); replyStream.close();`);
    await until("chat.messages.length === 2 && !chat.sending && chat.messages[1].content === 'Jawaban uji'");
    await check('starter prompt still sends question and displays reply', "!document.querySelector('.live-chat-welcome') && chat.messages[0].role === 'user' && chat.question === ''");
    await check('ordinary replies do not show a file card or toolbar download', "!document.querySelector('.chat-file-card') && [...document.querySelectorAll('.chat-export-wrap')].every((item) => getComputedStyle(item).display === 'none')");
    await evaluate("chat.messages[1].content = '<!-- FILE_DOCUMENT:pdf -->\\n# Laporan Uji\\nIsi dokumen'");
    await until("document.querySelector('.chat-file-card .chat-file-card-title')?.textContent === 'Laporan-Uji.pdf'");
    await check('requested PDF appears as a named file card', "document.querySelector('.chat-file-card .chat-file-card-title')?.textContent === 'Laporan-Uji.pdf' && [...document.querySelectorAll('.chat-file-card-actions button')].filter((button) => getComputedStyle(button).display !== 'none').length === 1");
    await check('file reply shows only the file card instead of repeating its contents', "!document.querySelector('.chat-message-actions') && ![...document.querySelectorAll('[x-chat-content]')].some((element) => element.innerText.includes('Isi dokumen'))");
    await evaluate("chat.messages[1].content = 'Baik, saya buatkan dalam format PDF. Saya gunakan penjelasan sebelumnya sebagai isi dokumen.\\n\\n<!-- FILE_DOCUMENT:pdf -->\\n# Laporan Uji\\nIsi dokumen'");
    await until("document.querySelector('.chat-file-card .chat-file-card-title')?.textContent === 'Laporan-Uji.pdf'");
    await until("[...document.querySelectorAll('[x-chat-content]')].some((element) => element.innerText.includes('Baik, saya buatkan dalam format PDF'))");
    await check('file introduction appears above the card while file contents stay hidden', "[...document.querySelectorAll('[x-chat-content]')].some((element) => element.innerText.includes('Baik, saya buatkan dalam format PDF')) && ![...document.querySelectorAll('[x-chat-content]')].some((element) => element.innerText.includes('Isi dokumen'))");
    await evaluate("chat.messages[0].content = 'Buatkan halaman login dalam HTML'; chat.messages[1].content = '<!-- FILE_DOCUMENT:html -->\\n<!doctype html><title>Login</title>'");
    await until("document.querySelector('.chat-file-card .chat-file-card-title')?.textContent.endsWith('.html')");
    await check('unnamed requested file gets a descriptive name from the request', "document.querySelector('.chat-file-card .chat-file-card-title')?.textContent.includes('login') && !document.querySelector('.chat-file-card .chat-file-card-title')?.textContent.startsWith('Dokumen')");
    await evaluate("chat.messages[1].content = 'Baik, saya siapkan skrip Python.\\n\\n<!-- FILE_DOCUMENT_FILE:hello.py -->\\nprint(\\\"Halo\\\")'");
    await until("document.querySelector('.chat-file-card .chat-file-card-title')?.textContent === 'hello.py'");
    await check('requested programming language appears as a correctly named source file', "document.querySelector('.chat-file-card .chat-file-card-title')?.textContent === 'hello.py' && document.querySelectorAll('.chat-file-card-actions button').length === 1");
    await evaluate("chat.messages[1].content = 'Baik, saya buatkan halaman login dengan HTML, CSS, dan JavaScript terpisah.\\n\\n<!-- FILE_DOCUMENT_FILE:index.html -->\\n<!doctype html>\\n<link rel=\\\"stylesheet\\\" href=\\\"style.css\\\">\\n<script src=\\\"script.js\\\"></script>\\n<!-- FILE_DOCUMENT_FILE:style.css -->\\nbody { color: #222; }\\n<!-- FILE_DOCUMENT_FILE:script.js -->\\nconsole.log(\\\"login\\\");'");
    await until("document.querySelectorAll('.chat-file-card').length === 3");
    await check('separate code assets appear as correctly named files', "[...document.querySelectorAll('.chat-file-card-title')].map((element) => element.textContent).join(',') === 'index.html,style.css,script.js' && document.querySelectorAll('.chat-file-card-actions button').length === 3");

    await go('/tanya-ai');
    await evaluate("window.chat = Alpine.$data(document.querySelector('.live-ai-chat')); window.sentUrls = []; window.initialNavigation = performance.timeOrigin;");
    await check('general chat opens without material', "document.querySelector('.live-chat-welcome h2').textContent.includes('Mau ngobrolin apa') && chat.messages.length === 0");
    await check('five mobile navigation items fit screen', "document.querySelectorAll('.mobile-nav a').length === 5 && document.documentElement.scrollWidth <= innerWidth");
    await evaluate(`window.fetch = async (url) => {
        sentUrls.push(url);
        return new Response('data: ' + JSON.stringify({chunk: 'Jawaban bebas'}) + '\\n\\n' + 'data: ' + JSON.stringify({done: true, title: 'Obrolan bebas', session_url: '/tanya/general-test', store_url: '/tanya/general-test', message: {id: sentUrls.length, content: 'Jawaban bebas', metadata: {source_snippets: []}}}) + '\\n\\n', {headers: {'Content-Type': 'text/event-stream'}});
    }; chat.question = 'Halo'; chat.sendQuestion();`);
    await until("!chat.sending && chat.messages.length === 2");
    await evaluate("chat.question = 'Lanjut'; chat.sendQuestion();");
    await until("!chat.sending && chat.messages.length === 4");
    await check('general chat switches to saved session without navigation', "sentUrls[0].endsWith('/tanya-ai') && sentUrls[1] === '/tanya/general-test' && location.pathname === '/tanya/general-test' && performance.timeOrigin === initialNavigation");
}
