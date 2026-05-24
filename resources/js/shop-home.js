document.addEventListener('DOMContentLoaded', () => {
    initHeroSlider();
    initFeaturedProducts();
    initPopularCollections();
    initDesignBanner();
    initCatalogShowcase();
    initCustomerReviews();
});

function initHeroSlider() {
    const slides = document.querySelectorAll('.hb-hero-slide');
    const dots = document.querySelectorAll('.hb-hero-dot');

    if (slides.length <= 1) {
        return;
    }

    let current = 0;
    let timer;

    const show = (index) => {
        slides.forEach((slide, i) => {
            slide.classList.toggle('is-active', i === index);
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === index);
        });
        current = index;
    };

    const next = () => show((current + 1) % slides.length);

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            show(index);
            resetTimer();
        });
    });

    const resetTimer = () => {
        clearInterval(timer);
        timer = setInterval(next, 6000);
    };

    show(0);
    resetTimer();
}

function initFeaturedProducts() {
    const section = document.querySelector('.hb-featured-products');
    if (!section) {
        return;
    }

    const track = section.querySelector('.hb-featured-track');
    const viewport = section.querySelector('.hb-featured-viewport');
    const prevBtn = section.querySelector('.hb-featured-prev');
    const nextBtn = section.querySelector('.hb-featured-next');
    const slides = section.querySelectorAll('.hb-featured-slide');

    if (!track || !viewport || slides.length === 0) {
        return;
    }

    const rtl = document.documentElement.dir === 'rtl';
    let index = 0;
    let autoTimer;
    let resizeTimer;

    const getGap = () => parseFloat(getComputedStyle(track).gap) || 24;

    const getStep = () => {
        const slide = slides[0];
        return slide ? slide.offsetWidth + getGap() : 0;
    };

    const getVisibleCount = () => {
        const step = getStep();
        if (!step) {
            return 1;
        }

        return Math.max(1, Math.floor((viewport.clientWidth + getGap()) / step));
    };

    const getMaxIndex = () => Math.max(0, slides.length - getVisibleCount());

    const applyTransform = () => {
        const step = getStep();
        const offset = index * step * (rtl ? 1 : -1);
        track.style.transform = `translate3d(${offset}px, 0, 0)`;

        if (prevBtn) {
            prevBtn.disabled = index <= 0;
        }
        if (nextBtn) {
            nextBtn.disabled = index >= getMaxIndex();
        }
    };

    const goTo = (newIndex) => {
        index = Math.min(getMaxIndex(), Math.max(0, newIndex));
        applyTransform();
    };

    const resetAuto = () => {
        clearInterval(autoTimer);
        if (getMaxIndex() > 0) {
            autoTimer = setInterval(() => {
                if (index >= getMaxIndex()) {
                    goTo(0);
                } else {
                    goTo(index + 1);
                }
            }, 6000);
        }
    };

    prevBtn?.addEventListener('click', () => {
        goTo(index - 1);
        resetAuto();
    });

    nextBtn?.addEventListener('click', () => {
        goTo(index + 1);
        resetAuto();
    });

    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => goTo(Math.min(index, getMaxIndex())), 150);
    });

    goTo(0);
    resetAuto();
}

function initPopularCollections() {
    const section = document.querySelector('.hb-popular-collections');
    if (!section) {
        return;
    }

    const track = section.querySelector('.hb-pc-track');
    const viewport = section.querySelector('.hb-pc-viewport');
    const prevBtn = section.querySelector('.hb-pc-prev');
    const nextBtn = section.querySelector('.hb-pc-next');
    const cards = section.querySelectorAll('.hb-pc-card');

    if (!track || !viewport || cards.length === 0) {
        return;
    }

    const rtl = document.documentElement.dir === 'rtl';
    let index = 0;
    let autoTimer;
    let resizeTimer;

    const getGap = () => {
        const styles = getComputedStyle(track);
        return parseFloat(styles.gap) || 24;
    };

    const getStep = () => {
        const card = cards[0];
        return card ? card.offsetWidth + getGap() : 0;
    };

    const getVisibleCount = () => {
        const step = getStep();
        if (!step) {
            return 1;
        }

        return Math.max(1, Math.floor((viewport.clientWidth + getGap()) / step));
    };

    const getMaxIndex = () => Math.max(0, cards.length - getVisibleCount());

    const applyTransform = () => {
        const step = getStep();
        const offset = index * step * (rtl ? 1 : -1);
        track.style.transform = `translate3d(${offset}px, 0, 0)`;
        prevBtn.disabled = index <= 0;
        nextBtn.disabled = index >= getMaxIndex();
    };

    const goTo = (newIndex) => {
        index = Math.min(getMaxIndex(), Math.max(0, newIndex));
        applyTransform();
    };

    const resetAuto = () => {
        clearInterval(autoTimer);
        if (getMaxIndex() > 0) {
            autoTimer = setInterval(() => {
                if (index >= getMaxIndex()) {
                    goTo(0);
                } else {
                    goTo(index + 1);
                }
            }, 7000);
        }
    };

    prevBtn?.addEventListener('click', () => {
        goTo(index - 1);
        resetAuto();
    });

    nextBtn?.addEventListener('click', () => {
        goTo(index + 1);
        resetAuto();
    });

    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            goTo(Math.min(index, getMaxIndex()));
        }, 150);
    });

    const revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    section.classList.add('is-visible', 'is-revealed');
                    revealObserver.disconnect();
                }
            });
        },
        { threshold: 0.08, rootMargin: '0px 0px -20px 0px' }
    );

    revealObserver.observe(section);

    if (section.getBoundingClientRect().top < window.innerHeight) {
        section.classList.add('is-visible', 'is-revealed');
    }

    goTo(0);
    resetAuto();
}

function initDesignBanner() {
    const section = document.querySelector('.hb-design-banner');
    if (!section) {
        return;
    }

    const revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    section.classList.add('is-visible');
                    revealObserver.disconnect();
                }
            });
        },
        { threshold: 0.15 }
    );

    revealObserver.observe(section);

    if (section.getBoundingClientRect().top < window.innerHeight) {
        section.classList.add('is-visible');
    }
}

function initCustomerReviews() {
    const section = document.querySelector('.hb-customer-reviews');
    if (!section) {
        return;
    }

    const track = section.querySelector('.hb-cr-track');
    const viewport = section.querySelector('.hb-cr-viewport');
    const prevBtn = section.querySelector('.hb-cr-prev');
    const nextBtn = section.querySelector('.hb-cr-next');
    const cards = section.querySelectorAll('.hb-cr-card');

    if (!track || !viewport || cards.length === 0) {
        return;
    }

    const rtl = document.documentElement.dir === 'rtl';
    let index = 0;
    let resizeTimer;

    const getGap = () => parseFloat(getComputedStyle(track).gap) || 16;

    const getStep = () => {
        const card = cards[0];
        return card ? card.offsetWidth + getGap() : 0;
    };

    const getVisibleCount = () => {
        const step = getStep();
        return step ? Math.max(1, Math.floor((viewport.clientWidth + getGap()) / step)) : 1;
    };

    const getMaxIndex = () => Math.max(0, cards.length - getVisibleCount());

    const applyTransform = () => {
        const step = getStep();
        const offset = index * step * (rtl ? 1 : -1);
        track.style.transform = `translate3d(${offset}px, 0, 0)`;
        if (prevBtn) {
            prevBtn.disabled = index <= 0;
        }
        if (nextBtn) {
            nextBtn.disabled = index >= getMaxIndex();
        }
    };

    const goTo = (newIndex) => {
        index = Math.min(getMaxIndex(), Math.max(0, newIndex));
        applyTransform();
    };

    prevBtn?.addEventListener('click', () => goTo(index - 1));
    nextBtn?.addEventListener('click', () => goTo(index + 1));

    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => goTo(Math.min(index, getMaxIndex())), 150);
    });

    goTo(0);
}

function initCatalogShowcase() {
    document.querySelectorAll('.hb-catalog-showcase').forEach((section) => {
        const tabs = section.querySelectorAll('.hb-catalog-tab');
        const panels = section.querySelectorAll('.hb-catalog-panel');

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const id = tab.getAttribute('data-catalog-tab');
                if (!id) {
                    return;
                }

                tabs.forEach((t) => {
                    const active = t === tab;
                    t.classList.toggle('is-active', active);
                    t.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                panels.forEach((panel) => {
                    const active = panel.getAttribute('data-catalog-panel') === id;
                    panel.classList.toggle('is-active', active);
                    panel.hidden = !active;
                });
            });
        });
    });
}
