import { Marked } from 'marked';
import DOMPurify from 'dompurify';
import katex from 'katex';
import hljs from 'highlight.js/lib/common';
import 'katex/dist/katex.min.css';
import '../css/chat-content.css';

const escapeHtml = value => String(value).replace(/[&<>"']/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
})[character]);

function closedFence(raw) {
    const match = raw.match(/^ {0,3}(`{3,}|~{3,})[^\n]*\n/);
    if (!match) return !/^ {0,3}(`{3,}|~{3,})/.test(raw);
    return new RegExp(`\\n {0,3}${match[1][0]}{${match[1].length},}[ \\t]*(?:\\n)?$`).test(raw);
}

function parseContent(content, streaming) {
    const visuals = [];
    const codes = [];
    const formulas = [];
    const mathToken = (raw, source, display) => ({ type: display ? 'chatMathBlock' : 'chatMathInline', raw, source, display });
    const renderMath = token => {
        const index = formulas.push(token) - 1;
        return `<${token.display ? 'div' : 'span'} data-chat-math="${index}"></${token.display ? 'div' : 'span'}>`;
    };
    const markdown = new Marked({ gfm: true, breaks: false, async: false, renderer: {
        html({ text }) { return escapeHtml(text); },
        image({ text }) { return escapeHtml(text); },
        checkbox({ checked }) { return checked ? '[x] ' : '[ ] '; },
        code(token) {
            const language = (token.lang || '').trim().split(/\s+/)[0].toLowerCase();
            if (['mermaid', 'chart'].includes(language) && visuals.length < 12) {
                const index = visuals.push({ kind: language, source: token.text, pending: streaming && !closedFence(token.raw) }) - 1;
                return `<section data-chat-visual="${index}"></section>`;
            }
            const index = codes.push({ source: token.text, language }) - 1;
            const highlighted = token.text.length <= 30000 && hljs.getLanguage(language)
                ? hljs.highlight(token.text, { language, ignoreIllegals: true }).value : escapeHtml(token.text);
            return `<div class="chat-code-block" data-chat-code="${index}"><pre><code>${highlighted}</code></pre></div>`;
        },
    }});
    markdown.use({ extensions: [
        {
            name: 'chatMathBlock', level: 'block',
            start: source => source.search(/\$\$|\\\[/),
            tokenizer(source) {
                const match = source.match(/^ {0,3}(?:\$\$([\s\S]+?)\$\$|\\\[([\s\S]+?)\\\])(?:[ \t]*\n)?/);
                if (match) return mathToken(match[0], (match[1] ?? match[2]).trim(), true);
            }, renderer: renderMath,
        },
        {
            name: 'chatMathInline', level: 'inline',
            start: source => source.search(/\$|\\\(/),
            tokenizer(source) {
                const match = source.match(/^(?:\\\(([^\n]+?)\\\)|\$(?!\$)((?:\\.|[^\\$\n])+?)\$(?!\d))/);
                if (match) return mathToken(match[0], (match[1] ?? match[2]).trim(), false);
            }, renderer: renderMath,
        },
    ]});
    const html = markdown.parse(content);
    const fragment = DOMPurify.sanitize(html, {
        RETURN_DOM_FRAGMENT: true, ALLOW_DATA_ATTR: false, ALLOW_ARIA_ATTR: false,
        ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'del', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li', 'hr', 'a', 'table', 'thead', 'tbody', 'tr', 'td', 'th', 'pre', 'code', 'span', 'div', 'section'],
        ALLOWED_ATTR: ['class', 'href', 'title', 'start', 'align', 'data-chat-visual', 'data-chat-code', 'data-chat-math'],
    });
    for (const link of fragment.querySelectorAll('a')) {
        if (!link.hasAttribute('href')) continue;
        try {
            const url = new URL(link.getAttribute('href'), location.href);
            if (!['https:', 'http:', 'mailto:'].includes(url.protocol)) throw new Error();
            link.href = url.href;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
        } catch { link.removeAttribute('href'); }
    }
    for (const table of fragment.querySelectorAll('table')) {
        const wrapper = document.createElement('div');
        wrapper.className = 'chat-table-scroll';
        wrapper.tabIndex = 0;
        wrapper.setAttribute('role', 'region');
        wrapper.setAttribute('aria-label', 'Tabel, geser untuk melihat kolom lain');
        table.replaceWith(wrapper);
        wrapper.append(table);
    }
    for (const element of fragment.querySelectorAll('[data-chat-math]')) {
        const formula = formulas[Number(element.dataset.chatMath)];
        try {
            if (formula.source.length > 4000) throw new Error();
            katex.render(formula.source, element, {
                displayMode: formula.display, throwOnError: true, trust: false,
                strict: 'ignore', maxExpand: 1000, maxSize: 20,
            });
        } catch {
            element.textContent = formula.source;
            element.className = 'chat-math-fallback';
        }
        element.removeAttribute('data-chat-math');
    }
    for (const element of fragment.querySelectorAll('[data-chat-code]')) {
        const code = codes[Number(element.dataset.chatCode)];
        const header = document.createElement('div');
        header.className = 'chat-code-header';
        const language = document.createElement('span');
        language.textContent = code.language || 'Kode';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'chat-code-copy';
        button.textContent = 'Salin';
        button.setAttribute('aria-label', 'Salin kode');
        button.addEventListener('click', async () => {
            try {
                if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(code.source);
                else {
                    const input = document.createElement('textarea');
                    input.value = code.source;
                    input.style.cssText = 'position:fixed;left:-10000px';
                    document.body.append(input);
                    input.select();
                    const copied = document.execCommand('copy');
                    input.remove();
                    button.focus({ preventScroll: true });
                    if (!copied) throw new Error();
                }
                button.textContent = 'Tersalin';
            } catch { button.textContent = 'Gagal menyalin'; }
        });
        header.append(language, button);
        element.prepend(header);
        element.removeAttribute('data-chat-code');
    }
    return { fragment, visuals };
}

let visualsModule;

function followBottom(element) {
    const scroller = element.closest('.live-chat-messages');
    const top = scroller?.scrollTop;
    const follow = scroller && scroller.scrollHeight - top - scroller.clientHeight < 180;
    return () => {
        if (follow && scroller.isConnected && scroller.scrollTop >= top - 2) {
            scroller.scrollTo({ top: scroller.scrollHeight, behavior: 'instant' });
        }
    };
}

function visualRecord(descriptor) {
    const wrapper = document.createElement('section');
    wrapper.className = 'chat-visual';
    wrapper.dataset.kind = descriptor.kind;
    wrapper.dataset.state = descriptor.pending ? 'pending' : 'loading';
    const heading = document.createElement('div');
    heading.className = 'chat-visual-heading';
    heading.textContent = descriptor.kind === 'mermaid' ? 'Diagram' : 'Grafik';
    const body = document.createElement('div');
    body.className = 'chat-visual-body';
    const status = document.createElement('p');
    status.className = 'chat-visual-status';
    status.setAttribute('role', 'status');
    status.textContent = descriptor.pending ? 'Menunggu data lengkap...' : 'Menyiapkan tampilan...';
    body.append(status);
    wrapper.append(heading, body);
    const record = { wrapper, dispose: null, removed: false, descriptor };
    if (descriptor.pending) return record;

    const source = document.createElement('details');
    source.className = 'chat-visual-source';
    const label = document.createElement('summary');
    label.textContent = descriptor.kind === 'mermaid' ? 'Lihat kode diagram' : 'Lihat data sumber';
    const pre = document.createElement('pre');
    pre.textContent = descriptor.source;
    source.append(label, pre);
    wrapper.append(source);
    record.start = async () => {
        try {
            visualsModule ??= import('./chat-visuals');
            const { renderChatDiagram, renderChatChart } = await visualsModule;
            if (record.removed) return;
            const keepBottom = followBottom(wrapper);
            record.dispose = await (descriptor.kind === 'mermaid' ? renderChatDiagram : renderChatChart)(body, descriptor.source);
            if (record.removed) record.dispose?.();
            else { wrapper.dataset.state = 'ready'; keepBottom(); }
        } catch (error) {
            if (record.removed) return;
            wrapper.dataset.state = 'error';
            const message = document.createElement('p');
            message.className = 'chat-visual-error';
            message.textContent = error.message || 'Tampilan belum bisa dibuat, sumber tetap bisa dibaca';
            body.replaceChildren(message);
        }
    };
    return record;
}

export function createChatContent(element) {
    let records = new Map();
    let previous = '';
    let destroyed = false;
    element.classList.add('chat-rich-content');
    return {
        update(content, streaming = false) {
            if (destroyed) return;
            const text = String(content || '');
            if (previous === `${streaming}:${text}`) return;
            previous = `${streaming}:${text}`;
            let parsed;
            try { parsed = parseContent(text, streaming); }
            catch {
                records.forEach(record => { record.removed = true; record.dispose?.(); });
                records.clear();
                element.textContent = text;
                return;
            }
            const next = new Map();
            const starts = [];
            const keepBottom = followBottom(element);
            // Reuse completed visuals while streamed prose changes around them.
            for (const placeholder of parsed.fragment.querySelectorAll('[data-chat-visual]')) {
                const index = Number(placeholder.dataset.chatVisual);
                const descriptor = parsed.visuals[index];
                const key = `${index}:${descriptor.kind}:${descriptor.pending}:${descriptor.source}`;
                let record = records.get(key);
                if (!record) {
                    record = visualRecord(descriptor);
                    if (record.start) starts.push(record.start);
                }
                next.set(key, record);
                placeholder.replaceWith(record.wrapper);
            }
            records.forEach((record, key) => {
                if (!next.has(key)) { record.removed = true; record.dispose?.(); }
            });
            records = next;
            element.replaceChildren(parsed.fragment);
            keepBottom();
            starts.forEach(start => start());
        },
        destroy() {
            destroyed = true;
            records.forEach(record => { record.removed = true; record.dispose?.(); });
            records.clear();
        },
    };
}
