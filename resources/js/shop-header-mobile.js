(function () {
    const sideNav = document.getElementById('hb-side-nav');
    const backdrop = document.querySelector('.hb-side-nav-backdrop');
    const openBtns = document.querySelectorAll('[data-mobile-menu-open]');
    const closeBtns = document.querySelectorAll('[data-mobile-menu-close]');

    if (!sideNav || openBtns.length === 0) {
        return;
    }

    const setOpen = (open) => {
        sideNav.classList.toggle('is-open', open);
        backdrop?.classList.toggle('is-open', open);
        document.body.classList.toggle('hb-menu-open', open);
        openBtns.forEach((btn) => btn.setAttribute('aria-expanded', open ? 'true' : 'false'));

        if (open) {
            sideNav.removeAttribute('hidden');
            backdrop?.removeAttribute('hidden');
            sideNav.querySelector('.hb-side-nav-close')?.focus();
        } else {
            sideNav.setAttribute('hidden', '');
            backdrop?.setAttribute('hidden', '');
        }
    };

    openBtns.forEach((btn) => {
        btn.addEventListener('click', () => setOpen(true));
    });

    closeBtns.forEach((btn) => {
        btn.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sideNav.classList.contains('is-open')) {
            setOpen(false);
            openBtns[0]?.focus();
        }
    });
})();
