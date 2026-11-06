import fs from 'node:fs/promises';
import path from 'node:path';

export async function checkWebChat({ cdp, evaluate, until, go, check, testingRoot }) {
    await go('/tanya-ai');
    await evaluate(`window.chat=Alpine.$data(document.querySelector('.live-ai-chat'));
        window.searchEvents=[]; const originalFetch=window.fetch;
        window.fetch=async (...args)=>{const response=await originalFetch(...args);
            if(response.headers.get('content-type')?.includes('text/event-stream')) response.clone().text().then(text=>window.searchEvents.push(text));
            return response;};
        chat.question='Cari berita terbaru'; chat.sendQuestion();`);
    await until("!chat.sending && chat.messages.length===2 && !!document.querySelector('.chat-web-sources') && searchEvents.length>0");
    await check('search progress travels through streaming response', "searchEvents[0].includes('searching') && searchEvents[0].includes('reading') && searchEvents[0].includes('answering')");
    await check('answer cites returned web URL', "chat.messages[1].content.includes('https://example.org/berita') && chat.messages[1].metadata.web_sources[0].read");
    const saved = await evaluate('location.pathname');
    await go(saved);
    await check('sources persist after refresh', "document.querySelector('.chat-web-sources').textContent.includes('Laporan sumber uji')");
    for (const width of [1440,390,320]) {
        await cdp('Emulation.setDeviceMetricsOverride',{width,height:844,deviceScaleFactor:1,mobile:width<760});
        await evaluate("document.querySelector('.chat-web-sources').open=true");
        await check(`${width}: compact sources fit viewport`, 'document.documentElement.scrollWidth<=innerWidth');
        await check(`${width}: source link is safe`, "document.querySelector('.chat-web-source-list a').href==='https://example.org/berita' && document.querySelector('.chat-web-source-list a').rel.includes('noopener')");
        await fs.writeFile(path.join(testingRoot,`web-chat-${width}.png`),Buffer.from((await cdp('Page.captureScreenshot',{format:'png'})).data,'base64'));
    }

    for (const width of [1440,390,320]) {
        await cdp('Emulation.setDeviceMetricsOverride',{width,height:844,deviceScaleFactor:1,mobile:width<760});
        await go('/tanya-ai');
        await evaluate(`window.chat=Alpine.$data(document.querySelector('.live-ai-chat'));
            window.fetch=async()=>new Response(new ReadableStream({start(controller){window.webProgressStream=controller}}),{headers:{'Content-Type':'text/event-stream'}});
            window.sendProgress=event=>webProgressStream.enqueue(new TextEncoder().encode('data: '+JSON.stringify(event)+'\\n\\n'));
            chat.question='Cari informasi terbaru'; void chat.sendQuestion();`);
        await until('!!window.webProgressStream');
        for (const phase of ['searching','reading','answering']) {
            await evaluate(`sendProgress({status:${JSON.stringify(phase)}})`);
            await until(`document.querySelector('.chat-web-status')?.dataset.phase === ${JSON.stringify(phase)}`);
            await check(`${width}: ${phase} animates without overflow`, "getComputedStyle(document.querySelector('.chat-web-status-icon'),'::before').animationName === 'chat-web-orbit' && document.documentElement.scrollWidth<=innerWidth");
        }
        await fs.writeFile(path.join(testingRoot,`web-progress-${width}.png`),Buffer.from((await cdp('Page.captureScreenshot',{format:'png'})).data,'base64'));
        await cdp('Emulation.setEmulatedMedia',{features:[{name:'prefers-reduced-motion',value:'reduce'}]});
        await check(`${width}: reduced motion stops all progress animations`, "getComputedStyle(document.querySelector('.chat-web-status-icon'),'::before').animationName === 'none' && getComputedStyle(document.querySelector('.chat-web-status-label')).animationName === 'none' && [...document.querySelectorAll('.chat-web-status-steps i')].every(dot=>getComputedStyle(dot).animationName === 'none')");
        await cdp('Emulation.setEmulatedMedia',{features:[]});
        await evaluate("sendProgress({chunk:'Jawaban uji'})");
        await until("chat.messages[1].content === 'Jawaban uji'");
        await check(`${width}: animation continues alongside streamed answer`, "chat.sending && document.querySelector('.chat-web-status')?.dataset.phase === 'answering' && getComputedStyle(document.querySelector('.chat-web-status-icon'),'::before').animationName === 'chat-web-orbit'");
        await evaluate("sendProgress({done:true,message:{id:123,content:'Jawaban uji',metadata:{}}}); webProgressStream.close()");
        await until("!chat.sending && !document.querySelector('.chat-web-status')");
        await check(`${width}: progress stops when answer completes`, "chat.messages[1].content === 'Jawaban uji'");
    }
}
