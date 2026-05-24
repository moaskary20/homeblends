(() => {
    const reveals = document.querySelectorAll('.hb-contact-reveal');

    const show = (el) => {
        const delay = Number(el.dataset.delay || 0);
        window.setTimeout(() => el.classList.add('is-visible'), delay);
    };

    if (reveals.length) {
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
                { threshold: 0.1, rootMargin: '0px 0px -30px 0px' }
            );

            reveals.forEach((el) => observer.observe(el));
        }
    }

    const parallax = document.querySelector('[data-parallax]');

    if (parallax && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const strength = Number(parallax.dataset.parallax || 0.15);

        window.addEventListener('scroll', () => {
            const offset = window.scrollY * strength;
            parallax.style.transform = `translate3d(0, ${offset}px, 0)`;
        }, { passive: true });
    }

    document.querySelectorAll('[data-tilt]').forEach((card) => {
        card.addEventListener('mousemove', (event) => {
            const rect = card.getBoundingClientRect();
            const x = (event.clientX - rect.left) / rect.width - 0.5;
            const y = (event.clientY - rect.top) / rect.height - 0.5;
            card.style.transform = `perspective(800px) rotateY(${x * 6}deg) rotateX(${y * -6}deg) translateY(-6px)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
        });
    });

    const form = document.querySelector('[data-contact-form]');

    if (form) {
        form.querySelectorAll('input, textarea').forEach((field) => {
            const wrap = field.closest('.hb-contact-field');

            field.addEventListener('focus', () => wrap?.classList.add('is-focused'));
            field.addEventListener('blur', () => wrap?.classList.remove('is-focused'));
        });

        form.addEventListener('submit', () => {
            const btn = form.querySelector('.hb-contact-form__submit');
            btn?.classList.add('is-loading');
        });
    }
})();
