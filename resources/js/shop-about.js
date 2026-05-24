(() => {
    const reveals = document.querySelectorAll('.hb-about-reveal');

    if (!reveals.length) {
        return;
    }

    const show = (el) => {
        const delay = Number(el.dataset.delay || 0);
        window.setTimeout(() => el.classList.add('is-visible'), delay);
    };

    if (!('IntersectionObserver' in window)) {
        reveals.forEach(show);
        return;
    }

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
})();
