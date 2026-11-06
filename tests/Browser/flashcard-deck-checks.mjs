import fs from 'node:fs/promises';
import path from 'node:path';

export async function checkFlashcardDeck({ cdp, evaluate, until, go, check, fixture }) {
    const data = JSON.parse(await fs.readFile(path.join(fixture, 'data.json'), 'utf8'));
    await cdp('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
    await go(data.document + '/flashcards');
    await evaluate("window.deck = Alpine.$data(document.querySelector('.live-flashcards')); window.originTime = performance.timeOrigin;");
    await check('list starts with five compact cards', "[...document.querySelectorAll('.live-deck-list li')].filter(el => el.offsetHeight).length === 5");
    await check('mobile has no horizontal overflow', 'document.documentElement.scrollWidth <= innerWidth');
    for (const [index, status] of ['sulit', 'ulang', 'paham'].entries()) {
        await evaluate(`document.querySelectorAll('.live-card-flip')[${index}].click();`);
        await evaluate(`document.querySelectorAll('.live-flashcard')[${index}].querySelector('input[value="${status}"]').form.requestSubmit();`);
        await until(`!deck.saving && deck.current === ${index + 1}`);
        await check(`${status} saved and advanced without reload`, `performance.timeOrigin === originTime && deck.cards[${index}].study_status === '${status}' && deck.cards[${index}].review_count === 1 && !deck.open`);
        await check(`${status} updates statistics`, `deck.count('${status}') === 1`);
    }
    await evaluate('new Promise(resolve => setTimeout(resolve, 300))');
    await evaluate("window.listPagerTop = document.querySelector('.live-deck-list .live-card-controls').getBoundingClientRect().top + scrollY; document.querySelector('.live-deck-list .live-card-controls button:last-child').click();");
    await until("[...document.querySelectorAll('.live-deck-list li')].filter(el => el.offsetHeight).length === 2");
    await check('last list page has two cards and stationary pager', "Math.abs(listPagerTop - (document.querySelector('.live-deck-list .live-card-controls').getBoundingClientRect().top + scrollY)) < 1");
    await evaluate("document.querySelector('.live-deck-list li:last-child button').click();");
    await check('list jumps to chosen card', 'deck.current === 6 && !deck.open');
    await evaluate("document.querySelectorAll('.live-card-flip')[6].click(); document.querySelectorAll('.live-flashcard')[6].querySelector('input[value=paham]').form.requestSubmit();");
    await until("!deck.saving && deck.cards[6].study_status === 'paham'");
    await check('last card stays in range with completion notice', "deck.current === 6 && deck.notice.includes('kartu terakhir')");
    await evaluate("deck.go(4); deck.open = true; window.realFetch = window.fetch; window.fetch = async () => new Response('{}', {status: 500}); document.querySelectorAll('.live-flashcard')[4].querySelector('form').requestSubmit();");
    await until('!deck.saving && !!deck.error');
    await check('failure preserves current card and answer', "deck.current === 4 && deck.open && deck.cards[4].study_status === 'baru'");
    await evaluate("window.requests = 0; window.fetch = async (...args) => { requests++; await new Promise(resolve => setTimeout(resolve, 400)); return realFetch(...args); }; const form = document.querySelectorAll('.live-flashcard')[4].querySelector('form'); form.requestSubmit(); form.requestSubmit(); deck.go(0);");
    await check('saving locks navigation', 'deck.current === 4');
    await until('!deck.saving && deck.current === 5');
    await check('double click sends one review', 'requests === 1 && deck.cards[4].review_count === 1');
    await go(data.document + '/flashcards');
    await evaluate("window.deck = Alpine.$data(document.querySelector('.live-flashcards'));");
    await check('reviews survive reload from database', "deck.count('sulit') === 2 && deck.count('ulang') === 1 && deck.count('paham') === 2");
    await cdp('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });
    await check('desktop has no horizontal overflow', 'document.documentElement.scrollWidth <= innerWidth');
}
