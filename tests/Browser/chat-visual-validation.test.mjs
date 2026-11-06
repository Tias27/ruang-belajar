import test from 'node:test';
import assert from 'node:assert/strict';
import { normalizeChatChart, validateChatDiagram } from '../../resources/js/chat-visuals.js';

const chart = overrides => JSON.stringify({
    type: 'bar', title: 'Hasil belajar', labels: ['Senin', 'Selasa'],
    datasets: [{ label: 'Nilai', data: [70, 85] }], ...overrides,
});

test('chart accepts typed data and discards arbitrary chart options', () => {
    const result = normalizeChatChart(chart({ options: { onClick: 'alert(1)' } }));
    assert.deepEqual(result, {
        type: 'bar', title: 'Hasil belajar', labels: ['Senin', 'Selasa'],
        datasets: [{ label: 'Nilai', data: [70, 85] }], xLabel: '', yLabel: '',
    });
    assert.equal(normalizeChatChart(chart({ type: 'line', datasets: [{ label: 'Suhu', data: [-10.5, 0] }] })).datasets[0].data[0], -10.5);
});

test('chart rejects malformed and unbounded data instead of coercing strings to numbers', () => {
    for (const source of ['{', 'null', '[]', chart({ type: 'scatter' }), chart({ labels: [] }),
        chart({ labels: Array(51).fill('Label') }), chart({ datasets: Array(7).fill({ data: [1, 2] }) }),
        chart({ datasets: [{ data: [1] }] }), chart({ datasets: [{ data: ['1', 2] }] }),
        chart({ datasets: [{ data: [null, 2] }] }), chart({ datasets: [{ data: [1e30, 2] }] }),
        chart({ labels: [' ', 'Selasa'] }), chart({ title: 'x'.repeat(201) })]) {
        assert.throws(() => normalizeChatChart(source), Error, source);
    }
});

test('pie charts require nonnegative data in one nonempty dataset', () => {
    assert.equal(normalizeChatChart(chart({ type: 'pie' })).type, 'pie');
    assert.equal(normalizeChatChart(chart({ type: 'doughnut' })).type, 'doughnut');
    for (const datasets of [[{ data: [-1, 2] }], [{ data: [0, 0] }], [{ data: [1, 2] }, { data: [3, 4] }]]) {
        assert.throws(() => normalizeChatChart(chart({ type: 'pie', datasets })));
    }
});

test('diagram accepts supported syntax without model-provided styles', () => {
    for (const source of ['flowchart TD\nA[Mulai] --> B[Selesai]', 'sequenceDiagram\nA->>B: Halo', 'mindmap\n  root((Belajar))\n    Membaca', 'pie\n"Belajar": 70\n"Istirahat": 30']) {
        assert.equal(validateChatDiagram(source), source);
    }
});

test('diagram rejects configuration, executable interactions, HTML and excess complexity', () => {
    for (const source of [
        '%%{init: {"securityLevel":"loose"}}%%\nflowchart TD\nA-->B',
        'flowchart TD\n%%{init: {}}%%\nA-->B',
        '---\nconfig: {}\n---\nflowchart TD\nA-->B',
        'flowchart TD\nA-->B; click A "https://example.test"',
        'flowchart TD\nA-->B\nclassDef evil fill:red;',
        'flowchart TD\nA-->B\nstyle A fill:red;',
        'flowchart TD\nA-->B\nlinkStyle 0 stroke:red;',
        'sequenceDiagram\nA->>B: Hallo\nlink A: Site @ https://example.test',
        'flowchart TD\nA[<img src=x onerror=alert(1)>]',
        'flowchart TD\nA@{ img: "https://example.test/tracker", label: "Photo" }',
        'flowchart TD\nA@{ icon: "logos:github", label: "Icon" }',
        'flowchart TD\nA[&lt;img src="https://example.test/tracker"&gt;]',
        'flowchart TD\nA[&#x3c;img src=x&#x3e;]',
        'flowchart TD\nA[#60;img src=x#62;]',
        'flowchart TD\nA[&amp;lt;img src=x&amp;gt;]',
        'flowchart TD\nA["url(https://example.test/tracker)"]',
        'unknownDiagram\nA-->B', 'flowchart TD\n' + 'A-->B;'.repeat(101),
        'flowchart TD\n' + 'A;'.repeat(161), 'flowchart TD\n' + 'A\n'.repeat(141),
        'flowchart TD\n' + 'x'.repeat(12000),
    ]) assert.throws(() => validateChatDiagram(source), Error, source.slice(0, 100));
});
