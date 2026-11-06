import fs from 'node:fs/promises';
import path from 'node:path';

export async function checkSummaryWait({ cdp, evaluate, until, go, check, fixture }) {
    const data = JSON.parse(await fs.readFile(path.join(fixture, 'data.json'), 'utf8'));
    for (const width of [390, 1440]) {
        await cdp('Emulation.setDeviceMetricsOverride', { width, height: 844, deviceScaleFactor: 1, mobile: width < 500 });
        await go(data.document);
        await evaluate(`
            window.resultUrl = [...document.querySelectorAll('a[href*="/summaries/"]')].find(a => !${JSON.stringify(Object.values(data.summary_states))}.includes(new URL(a.href).pathname)).href;
            window.polls = 0; window.posts = 0; window.allowFinish = false;
            window.nativeFetch = window.fetch;
            window.fetch = async (url, options = {}) => {
                if (String(url).includes('/summaries')) {
                    if (options.method === 'POST') window.posts++; else window.polls++;
                    const status = window.allowFinish ? 'completed' : 'processing';
                    return new Response(JSON.stringify({ status, url: resultUrl, status_url: resultUrl + '/status' }), {
                        status: status === 'completed' ? 200 : 202, headers: { 'Content-Type': 'application/json' },
                    });
                }
                return nativeFetch(url, options);
            };
            document.querySelector('#learn-summary').requestSubmit();
            document.querySelector('#learn-summary').requestSubmit();
        `);
        await until('window.polls >= 1');
        await check(`${width}: waiting keeps original page and one POST`, "window.pageIdentity === 'same-document' && performance.timeOrigin === originalNavigation && posts === 1");
        await check(`${width}: loading disables other AI actions`, "Alpine.$data(document.querySelector('#learn-summary')).loading && document.querySelector('#learn-chat button').disabled");
        await check(`${width}: no horizontal overflow`, 'document.documentElement.scrollWidth <= innerWidth');
        await evaluate('window.allowFinish = true');
        await until("location.pathname.startsWith('/summaries/') && !!document.querySelector('.live-summary-body')");
        await check(`${width}: completion opens saved summary`, "document.querySelector('.live-summary-body').textContent.includes('Isi ringkasan')");
    }

    await go(data.document);
    await evaluate(`
        window.resultUrl = [...document.querySelectorAll('a[href*="/summaries/"]')].find(a => !${JSON.stringify(Object.values(data.summary_states))}.includes(new URL(a.href).pathname)).href;
        window.posts = 0; window.offline = true; window.nativeFetch = window.fetch;
        window.fetch = async (url, options = {}) => {
            if (options.method === 'POST') window.posts++;
            else if (window.offline) throw new Error('Koneksi terputus');
            return new Response(JSON.stringify({ status: window.offline ? 'processing' : 'completed', url: resultUrl, status_url: resultUrl + '/status' }), { status: 202 });
        };
        document.querySelector('#learn-summary').requestSubmit();
    `);
    await until("!!Alpine.$data(document.querySelector('#learn-summary')).error");
    await check('poll failure unlocks page without losing existing request', "!Alpine.$data(document.querySelector('#learn-summary')).loading && !document.querySelector('#learn-chat button').disabled && posts === 1 && window.pageIdentity === 'same-document'");
    await evaluate("window.offline = false; document.querySelector('#learn-summary').requestSubmit();");
    await until("location.pathname.startsWith('/summaries/') && !!document.querySelector('.live-summary-body')");
    await check('retry opens completed result', "document.querySelector('.live-summary-body').textContent.includes('Isi ringkasan')");

    const completedUrl = await evaluate('location.href');
    await go(data.summary_states.processing);
    await evaluate(`window.fetch = async () => new Response(JSON.stringify({ status: 'completed', url: ${JSON.stringify(completedUrl)} }), { status: 200 });`);
    await until("!!document.querySelector('.live-summary-body')");
    await check('processing history opens result automatically', "document.querySelector('.live-summary-body').textContent.includes('Isi ringkasan')");

    await go(data.summary_states.failed);
    await check('failed summary offers retry', "document.querySelector('.live-summary form').textContent.includes('Coba lagi')");
    await evaluate(`window.fetch = async () => new Response(JSON.stringify({ status: 'completed', url: ${JSON.stringify(completedUrl)} }), { status: 200 }); document.querySelector('.live-summary form').requestSubmit();`);
    await until("!!document.querySelector('.live-summary-body')");
    await check('retry button works without material page state', "document.querySelector('.live-summary-body').textContent.includes('Isi ringkasan')");
}
