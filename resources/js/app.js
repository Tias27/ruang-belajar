import './bootstrap';
import Alpine from 'alpinejs';
import { renderLucideIcons } from './icons';
import { initPagination } from './pagination';
import { initMaterialMenus } from './material-menu';
import { flashcardDeck } from './flashcard-deck';
import { chatMaterials } from './chat-materials';
import { chatAttachmentPreview } from './chat-attachment-preview';
import { summaryRequest } from './summary-request';

window.Alpine = Alpine;
window.flashcardDeck = flashcardDeck;
window.chatMaterials = chatMaterials;
window.chatAttachmentPreview = chatAttachmentPreview;
window.summaryRequest = summaryRequest;
window.lucide = { createIcons: renderLucideIcons };
let chatContentModule;
Alpine.directive('chat-content', (element, { expression }, { effect, evaluateLater, cleanup }) => {
    // The renderer owns this subtree; generated content must never become Alpine directives.
    element._x_ignore = true;
    element.classList.add('chat-rich-content');
    const evaluate = evaluateLater(expression);
    let renderer, timer, disposed = false, latest = ['', false];
    const render = async () => {
        timer = null;
        try {
            chatContentModule ??= import('./chat-content');
            const { createChatContent } = await chatContentModule;
            if (disposed) return;
            renderer ??= createChatContent(element);
            renderer.update(...latest);
        } catch {
            if (!disposed) element.textContent = latest[0];
        }
    };
    effect(() => evaluate(value => {
        latest = [String(value?.[0] || ''), Boolean(value?.[1])];
        if (!timer) timer = setTimeout(render, 60);
    }));
    cleanup(() => { disposed = true; clearTimeout(timer); renderer?.destroy(); });
});
initMaterialMenus();
Alpine.start();
window.lucide.createIcons();
initPagination(Alpine, renderLucideIcons);

// Re-render only when views insert new icons (chat and Alpine templates).
let iconUpdateQueued = false;
new MutationObserver(records => {
    if (iconUpdateQueued || !records.some(record => [...record.addedNodes].some(node =>
        node.nodeType === 1 && (node.matches('i[data-lucide]') || node.querySelector('i[data-lucide]'))
    ))) return;
    iconUpdateQueued = true;
    requestAnimationFrame(() => {
        window.lucide.createIcons();
        iconUpdateQueued = false;
    });
}).observe(document.body, { childList: true, subtree: true });

window.addEventListener('pageshow', event => {
    if (event.persisted) window.location.reload();
});
