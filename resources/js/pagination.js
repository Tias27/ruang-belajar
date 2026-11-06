export function initPagination(Alpine, renderIcons) {
    const regionSelector = '[data-pagination-region]';
    let displayedUrl = new URL(window.location.href);
    const requests = new Map();
    const busyRequests = new WeakMap();
    const status = document.createElement('span');
    status.className = 'sr-only';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    document.body.append(status);

    const findRegion = (root, name) => [...root.querySelectorAll(regionSelector)]
        .find(region => region.dataset.paginationRegion === name);

    // Other pagers retain their DOM, so rebuild their links using the current URL.
    function syncLinks() {
        document.querySelectorAll('.compact-pagination[data-page-name]').forEach(pager => {
            pager.querySelectorAll('a[rel]').forEach(link => {
                const original = new URL(link.href);
                const url = new URL(window.location.href);
                url.searchParams.set(pager.dataset.pageName, original.searchParams.get(pager.dataset.pageName) || '1');
                url.hash = original.hash;
                link.href = url.href;
            });
        });
    }

    function announce(message) {
        status.textContent = message;
    }

    async function loadPage(url, names, { mode = 'push', focusName, direction } = {}) {
        const controller = new AbortController();
        names.forEach(name => {
            requests.get(name)?.abort();
            requests.set(name, controller);
        });
        const isCurrent = () => names.every(name => requests.get(name) === controller);
        const regions = names.map(name => findRegion(document, name)).filter(Boolean);
        regions.forEach(region => {
            busyRequests.set(region, controller);
            region.querySelector('[data-pagination-error]')?.remove();
            region.setAttribute('aria-busy', 'true');
        });
        announce('Memuat halaman daftar');
        const timeout = window.setTimeout(() => controller.abort(), 20000);

        try {
            const response = await fetch(url, {
                signal: controller.signal,
                credentials: 'same-origin',
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!isCurrent()) return;
            if (response.redirected && new URL(response.url).pathname !== url.pathname) {
                window.location.assign(response.url);
                return;
            }
            if (!response.ok || !response.headers.get('content-type')?.includes('text/html')) {
                throw new Error('Invalid pagination response');
            }

            const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (!isCurrent() || controller.signal.aborted) return;
            const replacements = regions.map(region => {
                const next = findRegion(parsed, region.dataset.paginationRegion);
                if (!next || next.querySelector('script')) throw new Error('Missing pagination region');
                const replacement = document.importNode(next, true);
                // Keep surrounding content and the viewport steady on shorter last pages.
                replacement.style.minHeight = `${region.getBoundingClientRect().height}px`;
                const pager = region.querySelector('.compact-pagination');
                const pagerOffset = pager ? pager.getBoundingClientRect().top - region.getBoundingClientRect().top : null;
                return [region, replacement, pagerOffset];
            });

            const scrollPosition = { left: window.scrollX, top: window.scrollY };
            const retainFocus = focusName && findRegion(document, focusName)?.contains(document.activeElement);
            Alpine.mutateDom(() => {
                for (const [region, replacement] of replacements) {
                    Alpine.destroyTree(region);
                    region.replaceWith(replacement);
                    window.renderIcons?.(replacement);
                    Alpine.initTree(replacement);
                }
            });
            renderIcons();

            const committedUrl = mode === 'pop' ? new URL(url) : new URL(window.location.href);
            names.forEach(name => {
                if (url.searchParams.has(name)) committedUrl.searchParams.set(name, url.searchParams.get(name));
                else committedUrl.searchParams.delete(name);
            });
            if (mode === 'push') window.history.pushState(window.history.state, '', committedUrl);
            if (mode === 'replace') window.history.replaceState(window.history.state, '', committedUrl);
            displayedUrl = committedUrl;
            syncLinks();
            await Alpine.nextTick();
            if (!isCurrent()) return;
            // Reserve missing rows above the controls, rather than below them.
            for (const [, replacement, pagerOffset] of replacements) {
                const pager = replacement.querySelector('.compact-pagination');
                const slot = pager?.closest('.pagination-slot');
                if (!slot || pagerOffset === null) continue;
                const currentOffset = pager.getBoundingClientRect().top - replacement.getBoundingClientRect().top;
                slot.style.paddingTop = `${Math.max(0, pagerOffset - currentOffset)}px`;
            }
            if (retainFocus) {
                const region = findRegion(document, focusName);
                const focusTarget = region?.querySelector(`.compact-pagination a[rel="${direction}"]`)
                    || region?.querySelector('.pagination-position');
                if (focusTarget) {
                    if (!focusTarget.matches('a')) focusTarget.tabIndex = -1;
                    focusTarget.focus({ preventScroll: true });
                }
            }
            window.scrollTo({ ...scrollPosition, behavior: 'instant' });
            announce('Halaman daftar diperbarui');
        } catch (error) {
            if (!isCurrent()) return;
            if (mode === 'pop') window.history.replaceState(window.history.state, '', displayedUrl);
            const region = findRegion(document, names[0]);
            if (region) {
                const message = document.createElement('div');
                message.className = 'pagination-error';
                message.dataset.paginationError = '';
                message.setAttribute('role', 'alert');
                message.append('Daftar belum berhasil dimuat. ');
                const retry = document.createElement('button');
                retry.type = 'button';
                retry.textContent = 'Coba lagi';
                retry.addEventListener('click', () => loadPage(url, names, {
                    mode: mode === 'pop' ? 'replace' : mode, focusName, direction,
                }));
                message.append(retry);
                region.append(message);
            }
            announce('Daftar belum berhasil dimuat');
        } finally {
            window.clearTimeout(timeout);
            regions.forEach(region => {
                if (busyRequests.get(region) === controller) region.removeAttribute('aria-busy');
            });
            names.forEach(name => {
                if (requests.get(name) === controller) requests.delete(name);
            });
        }
    }

    document.addEventListener('click', event => {
        const link = event.target.closest?.('.compact-pagination a[rel]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target === '_blank') return;
        const region = link.closest(regionSelector);
        const pager = link.closest('[data-page-name]');
        if (!region || !pager || region.dataset.paginationRegion !== pager.dataset.pageName) return;
        const destination = new URL(link.href);
        if (destination.origin !== window.location.origin || destination.pathname !== window.location.pathname) return;
        event.preventDefault();
        const url = new URL(window.location.href);
        url.searchParams.set(pager.dataset.pageName, destination.searchParams.get(pager.dataset.pageName) || '1');
        loadPage(url, [pager.dataset.pageName], { focusName: pager.dataset.pageName, direction: link.rel });
    });

    window.addEventListener('popstate', () => {
        new Set(requests.values()).forEach(controller => controller.abort());
        requests.clear();
        const url = new URL(window.location.href);
        if (url.origin !== displayedUrl.origin || url.pathname !== displayedUrl.pathname) return;
        const names = [...document.querySelectorAll(regionSelector)].map(region => region.dataset.paginationRegion)
            .filter(name => (url.searchParams.get(name) || '1') !== (displayedUrl.searchParams.get(name) || '1'));
        if (names.length) loadPage(url, names, { mode: 'pop' });
        else {
            displayedUrl = url;
        }
    });

    let layoutWidth = window.innerWidth;
    window.addEventListener('resize', () => {
        if (layoutWidth === window.innerWidth) return;
        layoutWidth = window.innerWidth;
        document.querySelectorAll(regionSelector).forEach(region => {
            region.style.removeProperty('min-height');
            region.querySelectorAll('.pagination-slot').forEach(slot => slot.style.removeProperty('padding-top'));
        });
    });
}
