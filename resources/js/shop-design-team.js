(() => {
    document.documentElement.classList.add('js');

    const reveals = document.querySelectorAll('.hb-dt-reveal');

    if (reveals.length) {
        const show = (el) => {
            const delay = Number(el.dataset.delay || 0);
            window.setTimeout(() => el.classList.add('is-visible'), delay);
        };

        if (!('IntersectionObserver' in window)) {
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
                { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
            );

            reveals.forEach((el) => observer.observe(el));
        }
    }

    document.querySelectorAll('.hb-dt-faq__item').forEach((item) => {
        item.addEventListener('toggle', () => {
            if (!item.open) {
                return;
            }

            const list = item.closest('.hb-dt-faq__list');
            if (!list) {
                return;
            }

            list.querySelectorAll('.hb-dt-faq__item[open]').forEach((other) => {
                if (other !== item) {
                    other.open = false;
                }
            });
        });
    });
})();
