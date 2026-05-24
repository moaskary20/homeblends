(function () {
    const page = document.querySelector('[data-product-page]');
    if (!page) {
        return;
    }

    const toast = page.querySelector('[data-pdp-toast]');
    const qtyInput = document.getElementById('product-qty');
    const variantSelect = document.getElementById('variant-select');

    function showToast(message) {
        if (!toast) {
            return;
        }
        toast.textContent = message;
        toast.classList.add('is-visible');
        clearTimeout(showToast._timer);
        showToast._timer = setTimeout(() => toast.classList.remove('is-visible'), 2800);
    }

    page.querySelector('[data-qty-minus]')?.addEventListener('click', () => {
        if (!qtyInput) {
            return;
        }
        qtyInput.value = String(Math.max(1, parseInt(qtyInput.value || '1', 10) - 1));
    });

    page.querySelector('[data-qty-plus]')?.addEventListener('click', () => {
        if (!qtyInput) {
            return;
        }
        const max = parseInt(qtyInput.max || '9999', 10);
        qtyInput.value = String(Math.min(max, parseInt(qtyInput.value || '1', 10) + 1));
    });

    document.querySelectorAll('[data-pdp-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.pdpTab;
            document.querySelectorAll('[data-pdp-tab]').forEach((t) => {
                t.classList.toggle('is-active', t === tab);
                t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
            });
            document.querySelectorAll('[data-pdp-panel]').forEach((panel) => {
                panel.classList.toggle('is-active', panel.dataset.pdpPanel === target);
            });
        });
    });

    async function addToCart(btn) {
        if (!btn || btn.disabled) {
            return;
        }

        const productId = parseInt(page.dataset.productId, 10);
        const quantity = parseInt(qtyInput?.value || '1', 10);
        const body = { product_id: productId, quantity: Math.max(1, quantity) };

        if (variantSelect?.value) {
            body.product_variant_id = parseInt(variantSelect.value, 10);
        }

        const token = localStorage.getItem('api_token');
        const originalHtml = btn.innerHTML;

        document.querySelectorAll('[data-pdp-add-cart]').forEach((b) => {
            b.classList.add('is-loading');
            b.disabled = true;
        });

        try {
            const res = await fetch(`${page.dataset.api}/cart/items`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Session-Id': page.dataset.sessionId,
                    ...(token ? { Authorization: `Bearer ${token}` } : {}),
                },
                body: JSON.stringify(body),
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                showToast(data.message || page.dataset.errorLabel || 'تعذّرت الإضافة');
                return;
            }

            const count = data.totals?.items_count ?? 0;
            window.dispatchEvent(new CustomEvent('cart:updated', { detail: { count } }));

            if (typeof window.refreshMiniCart === 'function') {
                window.refreshMiniCart();
            }

            document.querySelectorAll('[data-pdp-add-cart]').forEach((b) => {
                b.classList.add('is-success');
                if (b.classList.contains('hb-pdp-btn-cart') && b.querySelector('svg')) {
                    b.innerHTML = '✓ ' + (page.dataset.addedLabel || 'تمت الإضافة');
                }
            });

            showToast(page.dataset.addedLabel || 'تمت الإضافة إلى السلة');

            setTimeout(() => {
                document.querySelectorAll('[data-pdp-add-cart]').forEach((b) => {
                    b.classList.remove('is-success');
                    if (b.classList.contains('hb-pdp-btn-cart')) {
                        b.innerHTML = originalHtml;
                    }
                });
            }, 2200);
        } catch {
            showToast(page.dataset.errorLabel || 'تعذّرت الإضافة');
        } finally {
            const inStock = page.querySelector('.hb-pdp-badge.is-stock-ok');
            document.querySelectorAll('[data-pdp-add-cart]').forEach((b) => {
                b.classList.remove('is-loading');
                b.disabled = !inStock;
            });
        }
    }

    page.querySelectorAll('[data-pdp-add-cart]').forEach((btn) => {
        btn.addEventListener('click', () => addToCart(btn));
    });

    document.querySelectorAll('[data-gallery]').forEach((root) => {
        const main = root.querySelector('[data-gallery-main]');
        if (!main) {
            return;
        }
        root.querySelectorAll('[data-gallery-thumb]').forEach((btn) => {
            btn.addEventListener('click', () => {
                main.style.opacity = '0';
                setTimeout(() => {
                    main.src = btn.dataset.url;
                    main.style.opacity = '1';
                }, 120);
                root.querySelectorAll('[data-gallery-thumb]').forEach((t) => {
                    t.classList.toggle('border-amber-600', t === btn);
                    t.classList.toggle('border-transparent', t !== btn);
                });
            });
        });
    });
})();
