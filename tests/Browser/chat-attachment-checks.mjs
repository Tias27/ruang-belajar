import fs from 'node:fs/promises';
import path from 'node:path';

export async function checkAttachmentPreview({ cdp, evaluate, until, go, check, testingRoot }) {
    await go('/tanya-ai');
    await evaluate(`(async () => { window.chat = Alpine.$data(document.querySelector('.live-ai-chat'));
        const canvas = document.createElement('canvas'); canvas.width=180; canvas.height=320;
        const ctx=canvas.getContext('2d'); ctx.fillStyle='#c6d7bd'; ctx.fillRect(0,0,180,320);
        ctx.fillStyle='#263326'; ctx.font='20px sans-serif'; ctx.fillText('Foto uji',25,100);
        const blob=await new Promise(resolve=>canvas.toBlob(resolve,'image/png'));
        chat.handleFile(new File([blob],'gambar.png',{type:'image/png'})); })()`);
    await until('!!chat.imagePreview');
    await evaluate("chat.question='Jelaskan foto'; chat.sendQuestion()");
    await until("!chat.sending && location.pathname.startsWith('/tanya/') && chat.messages.length === 2");
    await check('upload receives persistent image URL', "chat.messages[0].metadata.image_path.includes('/messages/') && !chat.messages[0].metadata.image_path.startsWith('data:')");
    const saved = await evaluate('location.pathname');
    await go(saved);
    await until("document.querySelector('.live-chat-user-image img')?.naturalWidth > 0");
    await check('image reloads from storage', "document.querySelector('.live-chat-user-image img').naturalWidth === 180");

    for (const width of [1440,390,320]) {
        await cdp('Emulation.setDeviceMetricsOverride',{width,height:844,deviceScaleFactor:1,mobile:width<760});
        await evaluate("window.chat=Alpine.$data(document.querySelector('.live-ai-chat')); window.previewUrl=location.href; window.previewNavigation=performance.timeOrigin; document.querySelector('.live-chat-user-image').click()");
        await until("document.querySelector('.chat-attachment-preview').open && document.querySelector('.chat-attachment-full-image')?.naturalWidth > 0");
        await check(`${width}: image opens in modal without navigation`, "document.querySelector('.chat-attachment-preview').matches(':modal') && location.href===previewUrl && performance.timeOrigin===previewNavigation");
        await check(`${width}: preview fits viewport`, "(() => {const box=document.querySelector('.chat-attachment-preview').getBoundingClientRect(); return box.left>=0 && box.right<=innerWidth && box.top>=0 && box.bottom<=innerHeight})()");
        await check(`${width}: popup centered`, "(() => {const box=document.querySelector('.chat-attachment-preview').getBoundingClientRect(); return Math.abs(box.left + box.width/2-innerWidth/2)<2 && Math.abs(box.top+box.height/2-innerHeight/2)<2})()");
        await fs.writeFile(path.join(testingRoot,`attachment-preview-${width}.png`),Buffer.from((await cdp('Page.captureScreenshot',{format:'png'})).data,'base64'));
        await cdp('Input.dispatchKeyEvent',{type:'keyDown',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});
        await cdp('Input.dispatchKeyEvent',{type:'keyUp',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});
        await until("!document.querySelector('.chat-attachment-preview').open");
    }

    await evaluate(`(async () => { const response=await fetch('/tanya-ai/materi',{headers:{Accept:'application/json'}});
        const data=await response.json(); window.pdfMaterial=data.items.find(x=>x.extension==='PDF');
        window.textMaterial=data.items.find(x=>x.extension==='DOCX');
        chat.messages.push({id:'preview-files',role:'user',content:'Lihat materi',metadata:{materials:[pdfMaterial,textMaterial]}}); })()`);
    await until("document.querySelectorAll('.chat-message-material').length === 2");
    await evaluate("document.querySelector('.chat-message-material').click()");
    await until("chat.attachmentPreview.kind === 'pdf' && !!document.querySelector('.chat-attachment-pdf iframe')");
    await check('PDF loads inline inside modal', "(async () => {const r=await fetch(chat.attachmentPreview.url); return r.ok && r.headers.get('content-type').includes('application/pdf') && r.headers.get('content-disposition')==='inline' && location.href===previewUrl})()");
    await evaluate("document.querySelector('.chat-attachment-pdf button').click()");
    await until("!!document.querySelector('.chat-attachment-text')");
    await check('PDF offers text fallback', "document.querySelector('.chat-attachment-text').textContent.includes('Teks materi uji')");
    await evaluate("chat.closeAttachmentPreview(); document.querySelectorAll('.chat-message-material')[1].click()");
    await until("chat.attachmentPreview.kind === 'text' && !!document.querySelector('.chat-attachment-text')");
    await check('DOCX text displays safely without navigation', "document.querySelector('.chat-attachment-text').textContent.includes('<script>') && !window.badPreview && location.href===previewUrl && performance.timeOrigin===previewNavigation");
    await evaluate("document.querySelector('[aria-label=\"Tutup preview\"]').click()");
    await until("!document.querySelector('.chat-attachment-preview').open");
    await evaluate("chat.openFilePreview({id:'missing-file',title:'Tidak ada'})");
    await until('!!chat.attachmentPreview.error');
    await check('missing file shows error inside modal', 'location.href===previewUrl && !chat.attachmentPreview.loading');
}
