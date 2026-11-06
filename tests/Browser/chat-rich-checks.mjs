import fs from 'node:fs/promises';
import path from 'node:path';

const fence = (language, body) => '```' + language + '\n' + body + '\n```';
const chart = (type, data = [12, 18, 15]) => fence('chart', JSON.stringify({
    type, title: 'Contoh jumlah latihan', labels: ['Senin', 'Selasa', 'Rabu'],
    datasets: [{ label: 'Latihan', data }], xLabel: 'Hari', yLabel: 'Jumlah soal',
}));
const flow = fence('mermaid', 'flowchart LR\nA["Cahaya matahari"] --> B["Fotosintesis"]\nB --> C["Energi kimia"]');
const sourceCode = 'const label = "<img src=x onerror=window.richAttack=99>";\n\nfunction contoh() {\n    return label;\n}';
const markdown = [
    '## Belajar lewat contoh',
    'Penjelasan **penting**, *istilah*, dan `kode singkat` tetap terbaca.',
    '| Simbol | Arti |\n| --- | --- |\n| A \\| B | Pilihan |\n| C | Hasil |',
    'Hari | Jumlah soal\n--- | ---:\nSenin | 12\nSelasa | 18',
    '1. Siapkan bahan\n2. Amati proses\n   - Catat perubahan\n   - Bandingkan hasil',
    '> Contoh membantu memahami hubungan antarkonsep.',
    String.raw`Rumus inline $E=mc^2$ dan \(a^2+b^2=c^2\).`,
    String.raw`$$\frac{1}{2}+\frac{1}{2}=1$$`,
    String.raw`\[\sum_{i=1}^{3}i=6\]`,
    fence('javascript', sourceCode),
].join('\n\n');

export async function checkChatRich({ cdp, evaluate, until, go, check, testingRoot }) {
    let messageId = 1000;
    async function show(content, streaming = false) {
        await evaluate(`chat.messages = [{id: ${++messageId}, role: 'assistant', content: ${JSON.stringify(content)}, streaming: ${streaming}, metadata: null}]; void 0;`);
        await until("!!document.querySelector('.chat-rich-content')");
    }
    async function ready(count) {
        await until(`document.querySelectorAll('.chat-visual[data-state="ready"]').length === ${count}`);
    }

    for (const [width, height] of [[1440, 900], [390, 844], [320, 640]]) {
        await cdp('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 760 });
        await go('/tanya-ai');
        await evaluate("window.chat = Alpine.$data(document.querySelector('.live-ai-chat')); window.rect = selector => document.querySelector(selector).getBoundingClientRect(); window.richAttack = 0;");
        await show(markdown);
        await until("document.querySelectorAll('.chat-rich-content .katex').length === 4 && !!document.querySelector('.chat-code-copy')");
        await check(`${width}: tables support escaped pipes and optional outer pipes`, "document.querySelectorAll('.chat-table-scroll table').length === 2 && document.querySelector('.chat-table-scroll tbody td').textContent === 'A | B' && document.querySelectorAll('.chat-table-scroll')[1].textContent.includes('Selasa')");
        await check(`${width}: lists, nested points, quote and emphasis render`, "!!document.querySelector('.chat-rich-content ol li ul') && !!document.querySelector('.chat-rich-content blockquote') && !!document.querySelector('.chat-rich-content strong') && !!document.querySelector('.chat-rich-content em')");
        await check(`${width}: inline and display math render`, "document.querySelectorAll('.chat-rich-content .katex-display').length === 2 && document.querySelectorAll('.chat-rich-content .katex').length === 4");
        await check(`${width}: code preserves indentation and HTML remains literal`, `document.querySelector('.chat-code-block pre code').textContent.trimEnd() === ${JSON.stringify(sourceCode)} && !document.querySelector('.chat-code-block img') && window.richAttack === 0`);
        await check(`${width}: markdown does not widen page or hide composer`, "document.documentElement.scrollWidth <= innerWidth && rect('.live-chat-composer').bottom <= innerHeight && rect('[x-ref=messages]').height > 100");

        await show('### Proses dan data contoh\n\n' + flow + '\n\n' + chart('bar') + '\n\nJumlah latihan naik pada Selasa lalu turun sedikit pada Rabu.');
        await ready(2);
        await check(`${width}: diagram and chart render visually`, "!!document.querySelector('.chat-visual[data-kind=mermaid] svg') && !!document.querySelector('.chat-visual[data-kind=chart] canvas')");
        await check(`${width}: visual answer stays inside mobile or desktop page`, "document.documentElement.scrollWidth <= innerWidth && rect('.chat-visual[data-kind=chart]').right <= innerWidth && rect('.live-chat-composer').bottom <= innerHeight");
        await evaluate("document.querySelector('.chat-chart-data').open = true");
        await check(`${width}: chart values have readable table alternative`, "document.querySelector('.chat-chart-data').textContent.includes('Senin') && document.querySelector('.chat-chart-data').textContent.includes('18') && !!document.querySelector('.chat-chart-data table')");
        const screenshot = await cdp('Page.captureScreenshot', { format: 'png' });
        await fs.writeFile(path.join(testingRoot, `chat-rich-${width}.png`), Buffer.from(screenshot.data, 'base64'));
    }

    await show([
        fence('mermaid', 'mindmap\n  root((Fotosintesis))\n    Bahan\n      Air\n      Karbon dioksida\n    Hasil\n      Glukosa\n      Oksigen'),
        fence('mermaid', 'sequenceDiagram\nparticipant S as Siswa\nparticipant G as Guru\nS->>G: Mengapa hujan turun?\nG-->>S: Uap air mengembun menjadi tetes air'),
        chart('line'), chart('pie'), chart('doughnut'),
    ].join('\n\n'));
    await ready(5);
    await check('mind map, sequence and chart variants render', "document.querySelectorAll('.chat-visual[data-kind=mermaid] svg').length === 2 && document.querySelectorAll('.chat-visual[data-kind=chart] canvas').length === 3");
    await check('multiple visuals keep page width bounded', 'document.documentElement.scrollWidth <= innerWidth');

    await show(fence('javascript', sourceCode));
    await until("!!document.querySelector('.chat-code-copy')");
    await evaluate("Object.defineProperty(navigator, 'clipboard', {configurable: true, value: {writeText: async text => {window.copiedCode = text;}}}); document.querySelector('.chat-code-copy').click()");
    await until('typeof window.copiedCode === "string"');
    await check('copy code copies literal source without formatting', `window.copiedCode.trimEnd() === ${JSON.stringify(sourceCode)}`);

    const attack = [
        '<img src=x onerror="window.richAttack=1">',
        '<svg onload="window.richAttack=2"><script>window.richAttack=3</script></svg>',
        '<div x-data="{}" x-init="window.richAttack=4">Isi HTML</div>',
        '<a href="javascript:window.richAttack=5">Tautan berbahaya</a>',
        '[Jangan jalankan](javascript:alert%281%29)',
        '[Data berbahaya](data:text/html;base64,PHNjcmlwdD4=)',
        '[Sumber aman](https://example.com/reference)',
        String.raw`$\href{javascript:alert(1)}{tautan}$`,
    ].join('\n\n');
    await show(attack);
    await until("document.querySelector('.chat-rich-content').textContent.includes('Sumber aman')");
    await evaluate('new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))');
    await check('untrusted HTML and Alpine attributes never execute', "window.richAttack === 0 && !document.querySelector('.chat-rich-content script, .chat-rich-content [onerror], .chat-rich-content [onload], .chat-rich-content [x-init], .chat-rich-content [x-data]')");
    await check('dangerous URLs are stripped while regular links work', "![...document.querySelectorAll('.chat-rich-content a')].some(link => /^(javascript|data):/i.test(link.getAttribute('href') || '')) && !!document.querySelector('.chat-rich-content a[href=\"https://example.com/reference\"]')");

    const invalidVisuals = [
        fence('mermaid', 'flowchart LR\nA[unterminated'),
        fence('mermaid', 'flowchart LR\nA-->B\nclick A "javascript:window.richAttack=6"'),
        fence('mermaid', '%%{init: {"securityLevel":"loose"}}%%\nflowchart LR\nA-->B'),
        chart('pie', [-1, 2, 3]),
        fence('chart', '{"type":"bar","labels":["A","B"],"datasets":[{"label":"Uji","data":[1]}]}'),
    ].join('\n\n');
    await show(invalidVisuals);
    await until("document.querySelectorAll('.chat-visual[data-state=error]').length === 5");
    await check('bad diagrams and chart data show readable fallback', "document.querySelectorAll('.chat-visual-error').length === 5 && document.querySelectorAll('.chat-visual-source').length === 5 && window.richAttack === 0");
    await check('failed Mermaid parsing leaves no error overlay outside chat', "!document.querySelector('body > svg') && !document.querySelector('body > [id^=dmermaid]')");

    await show(fence('chart', JSON.stringify({
        type: 'bar', labels: ['A'], datasets: [{ label: 'Uji', data: [1] }],
        options: { animation: { onComplete: 'window.richAttack=7' } },
    })));
    await until("!!document.querySelector('.chat-visual[data-state=ready], .chat-visual[data-state=error]')");
    await check('chart configuration cannot execute model supplied code', 'window.richAttack === 0');

    await show('Penjelasan alur\n\n```mermaid\nflowchart LR\nA["Awal"]', true);
    await until("!!document.querySelector('.chat-visual[data-state=pending]')");
    await check('unfinished streaming diagram waits without parse errors or raw code', "!document.querySelector('.chat-visual-error') && !document.querySelector('.chat-visual svg') && !document.querySelector('.chat-code-block')");
    const streamContent = flow + '\n\n' + chart('bar') + '\n\nPenjelasan awal';
    await evaluate(`chat.messages[0].content = ${JSON.stringify(streamContent)}; void 0;`);
    await ready(2);
    await evaluate("window.previousDiagram = document.querySelector('.chat-visual[data-kind=mermaid] svg'); window.previousChart = document.querySelector('.chat-visual[data-kind=chart] canvas'); chat.messages[0].content += ' yang terus bertambah selama AI menjawab.'; void 0;");
    await until("document.querySelector('.chat-rich-content').textContent.includes('terus bertambah')");
    await check('streaming text reuses completed diagram and chart', "window.previousDiagram === document.querySelector('.chat-visual[data-kind=mermaid] svg') && window.previousChart === document.querySelector('.chat-visual[data-kind=chart] canvas')");
    await evaluate("chat.messages[0].streaming = false; chat.messages[0].id = 5555; void 0;");
    await ready(2);
    await check('finishing streamed message retains both visual answers', "document.querySelectorAll('.chat-visual[data-state=ready]').length === 2 && document.documentElement.scrollWidth <= innerWidth");
    await evaluate('chat.messages = []; void 0;');
    await until("!document.querySelector('.chat-visual')");
    await check('clearing conversation removes visual elements', "!document.querySelector('.chat-rich-content canvas, .chat-rich-content svg') && !window.previousChart.isConnected && !window.previousDiagram.isConnected");

    await show('```chart\n{"type":"bar"', true);
    await until("!!document.querySelector('.chat-visual[data-state=pending]')");
    await evaluate('chat.messages[0].streaming = false; void 0;');
    await until("!!document.querySelector('.chat-visual[data-state=error]')");
    await check('truncated finished chart shows error instead of waiting forever', "!!document.querySelector('.chat-visual-error') && !document.querySelector('.chat-visual[data-state=pending]')");

    await show('```mermaid', true);
    await until("!!document.querySelector('.chat-visual[data-state=pending]')");
    await check('opening fence alone waits for streaming data', "!document.querySelector('.chat-visual-error')");

    if (process.argv.includes('--provider-sample')) {
        const samples = JSON.parse(await fs.readFile(path.join(testingRoot, 'chat-rich-provider.json'), 'utf8'));
        for (const sample of samples) {
            await show(sample.answer);
            const count = [...sample.answer.matchAll(/^```(?:mermaid|chart)\s*$/gm)].length;
            if (count) await ready(count);
            await check(`${sample.metrics.case}: real provider answer renders without visual errors`, "!document.querySelector('.chat-visual-error') && document.documentElement.scrollWidth <= innerWidth");
        }
    }
}
