import fs from 'node:fs/promises';
import path from 'node:path';

export async function checkQuizDialog({ cdp, evaluate, until, go, check, fixture, testingRoot }) {
    const data = JSON.parse(await fs.readFile(path.join(fixture, 'data.json'), 'utf8'));
    for (const width of [1440, 360]) {
        await cdp('Emulation.setDeviceMetricsOverride', { width, height: 800, deviceScaleFactor: 1, mobile: width < 760 });
        for (const source of ['document', 'folder']) {
            await go(data[source]);
            await evaluate(`window.form = document.querySelector('#learn-quiz'); window.dialog = form.querySelector('dialog');
                window.trigger = form.querySelector('button'); trigger.focus(); window.originalHeight = form.getBoundingClientRect().height;
                window.settings = Alpine.$data(form); trigger.click();`);
            await until('dialog.open');
            await check(`${source} ${width}: opening popup keeps card size`, 'Math.abs(form.getBoundingClientRect().height - originalHeight) < 1');
            await check(`${source} ${width}: popup fits viewport`, 'dialog.getBoundingClientRect().left >= 0 && dialog.getBoundingClientRect().right <= innerWidth && dialog.scrollWidth <= dialog.clientWidth && dialog.getBoundingClientRect().bottom <= innerHeight');
            await check(`${source} ${width}: focus moves inside modal`, "dialog.matches(':modal') && dialog.contains(document.activeElement)");
            await evaluate("dialog.querySelector('input[value=essay]').click(); settings.count = 7;");
            await check(`${source} ${width}: selected options belong to original form`, "new FormData(form).get('question_type') === 'essay' && new FormData(form).get('question_count') === '7'");
            await evaluate("settings.count = 31;");
            await check(`${source} ${width}: invalid count blocked`, '!form.checkValidity()');
            await evaluate("settings.count = 7; dialog.querySelector('.quiz-settings-close').click()");
            await check(`${source} ${width}: close restores focus`, '!dialog.open && document.activeElement === trigger');
            await evaluate('trigger.click()');
            await cdp('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
            await cdp('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
            await until('!dialog.open');
            await check(`${source} ${width}: Escape preserves choices`, "settings.type === 'essay' && settings.count === 7");
            await evaluate('trigger.click()');
            if (source === 'folder') {
                await evaluate("Alpine.$data(document.querySelector('.live-folder')).selected = ['fixture-selected-file']");
                await check(`${width}: folder selection remains in form`, "new FormData(form).getAll('document_ids[]').includes('fixture-selected-file')");
            }
            if (source === 'document') {
                const capture = await cdp('Page.captureScreenshot', { format: 'png' });
                await fs.writeFile(path.join(testingRoot, `quiz-dialog-${width}.png`), Buffer.from(capture.data, 'base64'));
            }
            await evaluate(`form.addEventListener('submit', event => { event.preventDefault(); window.submitted = Object.fromEntries(new FormData(form)); });
                dialog.querySelector('button[type=submit]').click();`);
            await check(`${source} ${width}: submit keeps request fields and loading state`, "submitted.question_type === 'essay' && submitted.question_count === '7' && !!submitted._token && settings.loading && dialog.querySelector('button[type=submit]').disabled");
        }
    }
}
