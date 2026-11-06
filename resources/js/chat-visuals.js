const diagramTypes = /^(?:flowchart|graph|sequenceDiagram|mindmap|stateDiagram-v2|classDiagram|erDiagram|gantt|timeline|pie)\b/;
const chartTypes = new Set(['bar', 'line', 'pie', 'doughnut']);
const palette = ['#416f94', '#7898af', '#a4bac9', '#526c79', '#8e999b', '#c0c9c5'];
let mermaidModule;
let chartModule;
let purifierModule;
let diagramSequence = 0;
let diagramQueue = Promise.resolve();

function decodeDiagramEntities(text) {
    // Mermaid also accepts #60; entities without an ampersand.
    for (let pass = 0; pass < 6; pass++) {
        const decoded = text.replace(/(?:&?#(?:x([0-9a-f]+)|(\d+));|[&#](lt|gt|amp|quot|apos);)/gi, (match, hex, decimal, name) => {
            if (name) return { lt: '<', gt: '>', amp: '&', quot: '"', apos: "'" }[name.toLowerCase()];
            const value = Number.parseInt(hex || decimal, hex ? 16 : 10);
            return value <= 0x10ffff ? String.fromCodePoint(value) : match;
        });
        if (decoded === text) break;
        text = decoded;
    }
    return text;
}

export function validateChatDiagram(source) {
    if (typeof source !== 'string' || source.length > 12000) {
        throw new Error('Diagram terlalu besar untuk ditampilkan');
    }
    const text = source.trim();
    const lines = text.split(/\r?\n/);
    if (!text || lines.length > 140 || (text.match(/(?:--|==|\.\.)>/g) || []).length > 100
        || (text.match(/[;\n]/g) || []).length > 160 || (text.match(/[\[({]/g) || []).length > 180) {
        throw new Error('Diagram terlalu panjang, gunakan alur yang lebih sederhana');
    }
    const decoded = decodeDiagramEntities(text);
    if (!diagramTypes.test(text) || /^\s*---/m.test(decoded) || /%%\s*\{|@\s*\{/.test(decoded)
        || /(?:^|[;\r\n])\s*(?:click|link|links|style|classDef|linkStyle)\b/i.test(decoded)
        || /<\/?[a-z][^>]*>|url\s*\(|@import/i.test(decoded)) {
        throw new Error('Format diagram tidak didukung, gunakan label teks tanpa tautan atau pengaturan gaya');
    }
    return text;
}

function textField(value, fallback = '', maximum = 200) {
    if (value === undefined) return fallback;
    if (typeof value !== 'string' || value.trim().length > maximum) {
        throw new Error('Judul dan label grafik harus berupa teks singkat');
    }
    return value.trim();
}

function hasExternalUrl(value) {
    return [...value.matchAll(/url\(\s*(['"]?)(.*?)\1\s*\)/gi)].some(match => !match[2].trim().startsWith('#'));
}

export function normalizeChatChart(source) {
    let data;
    try {
        if (typeof source !== 'string' || source.length > 20000) throw new Error();
        data = JSON.parse(source);
    } catch {
        throw new Error('Data grafik belum lengkap atau format JSON tidak valid');
    }
    if (!data || Array.isArray(data) || !chartTypes.has(data.type)) {
        throw new Error('Jenis grafik harus bar, line, pie, atau doughnut');
    }
    if (!Array.isArray(data.labels) || !data.labels.length || data.labels.length > 50
        || !Array.isArray(data.datasets) || !data.datasets.length || data.datasets.length > 6) {
        throw new Error('Grafik memerlukan 1-50 label dan 1-6 kelompok data');
    }
    const labels = data.labels.map(value => {
        const label = textField(value, '', 120);
        if (!label) throw new Error('Label grafik tidak boleh kosong');
        return label;
    });
    const circular = data.type === 'pie' || data.type === 'doughnut';
    const datasets = data.datasets.map((dataset, index) => {
        if (!dataset || typeof dataset !== 'object' || Array.isArray(dataset)
            || !Array.isArray(dataset.data) || dataset.data.length !== labels.length) {
            throw new Error('Jumlah angka grafik harus sama dengan jumlah label');
        }
        const values = dataset.data.map(value => {
            if (typeof value !== 'number' || !Number.isFinite(value) || Math.abs(value) > Number.MAX_SAFE_INTEGER) {
                throw new Error('Nilai grafik harus angka valid dalam rentang yang dapat ditampilkan');
            }
            if (circular && value < 0) throw new Error('Grafik bagian tidak boleh memiliki nilai negatif');
            return value;
        });
        if (circular && !values.some(value => value > 0)) throw new Error('Grafik bagian memerlukan setidaknya satu nilai positif');
        return { label: textField(dataset.label, `Data ${index + 1}`, 120) || `Data ${index + 1}`, data: values };
    });
    if (circular && datasets.length !== 1) throw new Error('Grafik pie dan doughnut menggunakan satu kelompok data');
    return {
        type: data.type, title: textField(data.title), labels, datasets,
        xLabel: textField(data.xLabel, '', 120), yLabel: textField(data.yLabel, '', 120),
    };
}

async function diagramRenderer() {
    mermaidModule ??= import('mermaid').then(({ default: mermaid }) => {
        mermaid.initialize({
            startOnLoad: false, securityLevel: 'strict', suppressErrorRendering: true,
            maxTextSize: 12000, maxEdges: 100, theme: 'base', htmlLabels: false,
            fontFamily: 'sans-serif',
            themeVariables: {
                fontFamily: 'sans-serif', fontSize: '13px', background: '#fffef9',
                primaryColor: '#edf3f8', primaryTextColor: '#262626', primaryBorderColor: '#7898af',
                secondaryColor: '#f0f3eb', secondaryTextColor: '#262626', secondaryBorderColor: '#8e999b',
                tertiaryColor: '#fffef9', tertiaryTextColor: '#262626', tertiaryBorderColor: '#c0c9c5',
                lineColor: '#526c79', textColor: '#262626', mainBkg: '#edf3f8', nodeBorder: '#7898af',
                clusterBkg: '#fafbf6', clusterBorder: '#c0c9c5', edgeLabelBackground: '#fffef9',
                actorBkg: '#edf3f8', actorBorder: '#7898af', actorTextColor: '#262626',
                actorLineColor: '#8e999b', signalColor: '#526c79', signalTextColor: '#262626',
                labelBoxBkgColor: '#fffef9', labelBoxBorderColor: '#c0c9c5', labelTextColor: '#262626',
                noteBkgColor: '#f0f3eb', noteBorderColor: '#c0c9c5', noteTextColor: '#262626',
                pie1: palette[0], pie2: palette[1], pie3: palette[2], pie4: palette[3], pie5: palette[4], pie6: palette[5],
                pie7: palette[0], pie8: palette[1], pie9: palette[2], pie10: palette[3], pie11: palette[4], pie12: palette[5],
                cScale0: '#edf3f8', cScale1: '#e2ebf2', cScale2: '#f0f3eb', cScale3: '#e8eeeb',
                cScale4: '#e8edf1', cScale5: '#f3f2ec', cScale6: '#edf3f8', cScale7: '#e2ebf2',
                cScale8: '#f0f3eb', cScale9: '#e8eeeb', cScale10: '#e8edf1', cScale11: '#f3f2ec',
            },
            flowchart: { htmlLabels: false, useMaxWidth: false, curve: 'linear', padding: 12 },
            sequence: { useMaxWidth: false, actorFontSize: 12, messageFontSize: 12, noteFontSize: 12 },
            mindmap: { useMaxWidth: false },
        });
        return mermaid;
    });
    return mermaidModule;
}

export async function renderChatDiagram(container, source) {
    const text = validateChatDiagram(source);
    const task = diagramQueue.then(async () => {
        const mermaid = await diagramRenderer();
        purifierModule ??= import('dompurify').then(module => module.default(window));
        const purifier = await purifierModule;
        const id = `chat-diagram-${++diagramSequence}`;
        const sandbox = document.createElement('div');
        sandbox.style.cssText = 'position:fixed;left:-100000px;top:0;width:1200px;visibility:hidden;pointer-events:none';
        sandbox.setAttribute('aria-hidden', 'true');
        document.body.append(sandbox);
        try {
            await mermaid.parse(text);
            const result = await mermaid.render(id, text, sandbox);
            const fragment = purifier.sanitize(result.svg, {
                USE_PROFILES: { svg: true, svgFilters: true }, RETURN_DOM_FRAGMENT: true,
                FORBID_TAGS: ['foreignObject', 'script', 'iframe', 'object', 'embed', 'image', 'a', 'animate', 'set', 'animateMotion', 'animateTransform'],
            });
            const svg = fragment.querySelector('svg');
            if (!svg) throw new Error('Diagram belum berhasil dibuat');
            // Keep SVG references local; model-provided content never loads external assets.
            for (const node of fragment.querySelectorAll('*')) {
                for (const attribute of [...node.attributes]) {
                    if (/^(?:href|xlink:href)$/i.test(attribute.name) && !attribute.value.startsWith('#')) node.removeAttribute(attribute.name);
                    if (hasExternalUrl(attribute.value)) node.removeAttribute(attribute.name);
                }
                if (node.tagName.toLowerCase() === 'style' && (/@import/i.test(node.textContent) || hasExternalUrl(node.textContent))) node.remove();
            }
            svg.setAttribute('role', 'img');
            if (!svg.hasAttribute('aria-label') && !svg.hasAttribute('aria-labelledby')) svg.setAttribute('aria-label', 'Diagram penjelasan');
            const viewBoxWidth = Number(svg.getAttribute('viewBox')?.split(/[ ,]+/)[2]);
            if (Number.isFinite(viewBoxWidth) && viewBoxWidth > 0) {
                svg.style.width = `${Math.min(16000, Math.ceil(viewBoxWidth))}px`;
                svg.style.maxWidth = 'none';
                svg.style.height = 'auto';
            }
            const viewport = document.createElement('div');
            viewport.className = 'chat-diagram-viewport';
            viewport.tabIndex = 0;
            viewport.setAttribute('role', 'region');
            viewport.setAttribute('aria-label', 'Diagram, geser untuk melihat seluruh bagian');
            viewport.append(fragment);
            container.replaceChildren(viewport);
            return () => viewport.remove();
        } catch {
            throw new Error('Diagram belum bisa ditampilkan. Kode diagram tetap tersedia');
        } finally {
            sandbox.remove();
            document.getElementById(`d${id}`)?.remove();
        }
    });
    diagramQueue = task.catch(() => {});
    return task;
}

function appendText(parent, tag, text, className) {
    const element = document.createElement(tag);
    element.textContent = text;
    if (className) element.className = className;
    parent.append(element);
    return element;
}

export async function renderChatChart(container, source) {
    const data = normalizeChatChart(source);
    chartModule ??= import('chart.js/auto').then(module => module.default);
    const Chart = await chartModule;
    const figure = document.createElement('figure');
    figure.className = 'chat-chart';
    if (data.title) appendText(figure, 'figcaption', data.title, 'chat-chart-caption');
    const plot = document.createElement('div');
    plot.className = 'chat-chart-plot';
    plot.style.cssText = 'position:relative;min-width:0;height:260px;max-width:100%';
    const canvas = document.createElement('canvas');
    canvas.setAttribute('role', 'img');
    canvas.setAttribute('aria-label', data.title || 'Grafik data');
    canvas.textContent = 'Nilai lengkap tersedia pada tabel data di bawah grafik';
    plot.append(canvas);
    figure.append(plot);
    const details = appendText(figure, 'details', '', 'chat-chart-data');
    appendText(details, 'summary', 'Lihat data');
    const tableScroll = appendText(details, 'div', '', 'chat-chart-table-scroll');
    tableScroll.tabIndex = 0;
    const table = appendText(tableScroll, 'table', '');
    const header = appendText(appendText(table, 'thead', ''), 'tr', '');
    appendText(header, 'th', data.xLabel || 'Label').scope = 'col';
    data.datasets.forEach(dataset => appendText(header, 'th', dataset.label).scope = 'col');
    const body = appendText(table, 'tbody', '');
    data.labels.forEach((label, index) => {
        const row = appendText(body, 'tr', '');
        appendText(row, 'th', label).scope = 'row';
        data.datasets.forEach(dataset => appendText(row, 'td', new Intl.NumberFormat('id-ID', { maximumSignificantDigits: 15 }).format(dataset.data[index])));
    });
    container.replaceChildren(figure);
    const circular = data.type === 'pie' || data.type === 'doughnut';
    let chart;
    try {
        chart = new Chart(canvas, {
            type: data.type,
            data: {
                labels: data.labels,
                datasets: data.datasets.map((dataset, index) => ({
                    ...dataset,
                    backgroundColor: circular ? data.labels.map((_, labelIndex) => palette[labelIndex % palette.length]) : palette[index],
                    borderColor: circular ? '#fffef9' : palette[index],
                    borderWidth: circular ? 2 : 1.5,
                    pointRadius: 3, pointHoverRadius: 5, tension: 0, fill: false,
                })),
            },
            options: {
                responsive: true, maintainAspectRatio: false, animation: false,
                color: '#4b4b4b', font: { size: 12 },
                plugins: {
                    legend: { display: circular || data.datasets.length > 1, position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, padding: 12, font: { size: 12 } } },
                    tooltip: { enabled: true },
                },
                scales: circular ? {} : {
                    x: { title: { display: !!data.xLabel, text: data.xLabel }, grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 8, maxRotation: 45, font: { size: 11 } } },
                    y: { beginAtZero: data.type === 'bar', title: { display: !!data.yLabel, text: data.yLabel }, grid: { color: '#e1e5dc' }, ticks: { maxTicksLimit: 6, font: { size: 11 } } },
                },
            },
        });
    } catch {
        Chart.getChart(canvas)?.destroy();
        figure.remove();
        throw new Error('Grafik belum bisa ditampilkan. Data aslinya tetap tersedia');
    }
    return () => { chart.destroy(); figure.remove(); };
}
