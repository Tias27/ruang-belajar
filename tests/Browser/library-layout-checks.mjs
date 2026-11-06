import fs from 'node:fs/promises';
import path from 'node:path';

export async function checkLibraryLayout({ cdp, evaluate, until, go, check, testingRoot, clickPage }) {
    for (const [width, height] of [[1440,900],[1164,540],[820,600],[390,844]]) {
        await cdp('Emulation.setDeviceMetricsOverride', {width,height,deviceScaleFactor:1,mobile:width<760});
        await go('/documents');
        await evaluate("window.rect = selector => document.querySelector(selector).getBoundingClientRect()");
        await evaluate("document.querySelector('input[name=q]').focus()");
        await check(`${width}: search focus ring is not clipped by an ancestor`, `(() => {
            const input = document.querySelector('input[name=q]');
            const box = input.getBoundingClientRect();
            for (let parent = input.parentElement; parent; parent = parent.parentElement) {
                if (getComputedStyle(parent).overflowX === 'visible') continue;
                const bounds = parent.getBoundingClientRect();
                if (box.left - 6 < bounds.left || box.right + 6 > bounds.right) return false;
            }
            return document.documentElement.scrollWidth <= innerWidth;
        })()`);
        await check(`${width}: folders compact with no empty grid column`, "[...document.querySelectorAll('.live-folder-card')].every(card => card.getBoundingClientRect().height < 100 && Math.abs(card.getBoundingClientRect().width - rect('.compact-folder-list').width) < 2)");
        await check(`${width}: file actions aligned right`, "Math.abs(rect('.live-row-actions').right - rect('.live-material-row').right) < 2");
        await evaluate("document.querySelector('.live-row-menu summary').scrollIntoView({block:'center'}); document.querySelector('.live-row-menu summary').click()");
        await until("!!document.querySelector('.live-row-menu-content:popover-open') && getComputedStyle(document.querySelector('.live-row-menu-content:popover-open')).visibility === 'visible'");
        await check(`${width}: entire menu inside viewport`, "rect('.live-row-menu-content:popover-open').left >= 0 && rect('.live-row-menu-content:popover-open').top >= 0 && rect('.live-row-menu-content:popover-open').bottom <= innerHeight && rect('.live-row-menu-content:popover-open').right <= innerWidth");
        await check(`${width}: menu above sidebar and surrounding content`, "(() => {const panel=document.querySelector('.live-row-menu-content:popover-open'); const r=panel.getBoundingClientRect(); return panel.contains(document.elementFromPoint(r.left+20,r.top+20));})()");
        await evaluate("document.querySelector('.live-row-menu-content:popover-open button[aria-haspopup=dialog]').click()");
        await until("!!document.querySelector('.quiz-settings-dialog[open]')");
        await check(`${width}: quiz opens modal rather than clipped submenu`, "document.querySelector('.quiz-settings-dialog[open]').matches(':modal')");
        await evaluate("document.querySelector('.quiz-settings-dialog[open] .quiz-settings-close').click(); document.querySelector('.live-row-menu[open] .live-row-menu-close').click()");
        await until("!document.querySelector('.live-row-menu-content:popover-open')");
        await clickPage('page','next',2);
        await evaluate("document.querySelector('.live-row-menu summary').click()");
        await until("!!document.querySelector('.live-row-menu-content:popover-open') && getComputedStyle(document.querySelector('.live-row-menu-content:popover-open')).visibility === 'visible'");
        await check(`${width}: menu works after async pagination`, "rect('.live-row-menu-content:popover-open').left >= 0 && rect('.live-row-menu-content:popover-open').bottom <= innerHeight");
        const screenshot = await cdp('Page.captureScreenshot',{format:'png'});
        await fs.writeFile(path.join(testingRoot,`library-layout-${width}.png`),Buffer.from(screenshot.data,'base64'));
    }
}
