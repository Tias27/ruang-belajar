export function initMaterialMenus() {
    function position(menu, panel) {
        const anchor = menu.querySelector('summary').getBoundingClientRect();
        const nav = document.querySelector('.mobile-nav');
        const bottom = nav && getComputedStyle(nav).display !== 'none'
            ? nav.getBoundingClientRect().top - 12 : innerHeight - 12;
        panel.style.maxHeight = `${Math.max(80, bottom - 12)}px`;
        const { width, height } = panel.getBoundingClientRect();
        panel.style.left = `${Math.max(12, Math.min(anchor.right - width, innerWidth - width - 12))}px`;
        const top = anchor.bottom + 6 + height <= bottom ? anchor.bottom + 6 : anchor.top - height - 6;
        panel.style.top = `${Math.max(12, Math.min(top, bottom - height))}px`;
    }

    document.addEventListener('toggle', event => {
        const menu = event.target;
        if (!menu.matches('.live-row-menu')) return;
        const panel = menu.querySelector('.live-row-menu-content');
        if (!menu.open) {
            panel.hidePopover();
            return;
        }
        document.querySelectorAll('.live-row-menu[open]').forEach(other => {
            if (other !== menu) other.open = false;
        });
        // Top layer avoids clipping by library containers and the sidebar.
        panel.style.visibility = 'hidden';
        panel.showPopover();
        requestAnimationFrame(() => {
            if (!menu.isConnected || !menu.open) return;
            position(menu, panel);
            panel.style.visibility = 'visible';
        });
    }, true);

    const reposition = () => document.querySelectorAll('.live-row-menu[open]').forEach(menu => {
        const panel = menu.querySelector('.live-row-menu-content');
        if (panel.matches(':popover-open')) position(menu, panel);
    });
    window.addEventListener('resize', reposition);
    window.addEventListener('scroll', reposition, true);
}
