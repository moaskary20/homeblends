(() => {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const reveals = document.querySelectorAll('.hb-legal-reveal');
    if (reveals.length) {
        const show = (el) => {
            const delay = reducedMotion ? 0 : Number(el.dataset.delay || 0);
            window.setTimeout(() => el.classList.add('is-visible'), delay);
        };

        if (!('IntersectionObserver' in window) || reducedMotion) {
            reveals.forEach(show);
        } else {
            const observer = new IntersectionObserver(
                (entries, obs) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) {
                            return;
                        }
                        show(entry.target);
                        obs.unobserve(entry.target);
                    });
                },
                { threshold: 0.08, rootMargin: '0px 0px -40px 0px' }
            );
            reveals.forEach((el) => observer.observe(el));
        }
    }

    const tocLinks = document.querySelectorAll('[data-toc-link]');
    const sections = Array.from(tocLinks)
        .map((link) => {
            const id = link.getAttribute('href')?.replace('#', '');
            const section = id ? document.getElementById(id) : null;
            return section ? { link, section } : null;
        })
        .filter(Boolean);

    if (sections.length === 0) {
        return;
    }

    const setActive = (id) => {
        tocLinks.forEach((link) => {
            const href = link.getAttribute('href')?.replace('#', '');
            link.classList.toggle('is-active', href === id);
        });
    };

    if ('IntersectionObserver' in window && !reducedMotion) {
        const sectionObserver = new IntersectionObserver(
            (entries) => {
                const visible = entries
                    .filter((e) => e.isIntersecting)
                    .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

                if (visible?.target?.id) {
                    setActive(visible.target.id);
                }
            },
            { rootMargin: '-20% 0px -55% 0px', threshold: [0, 0.25, 0.5] }
        );

        sections.forEach(({ section }) => sectionObserver.observe(section));
    }

    tocLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const id = link.getAttribute('href')?.replace('#', '');
            const target = id ? document.getElementById(id) : null;
            if (!target) {
                return;
            }

            event.preventDefault();
            target.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
            setActive(id);
            history.replaceState(null, '', `#${id}`);
        });
    });
})();
