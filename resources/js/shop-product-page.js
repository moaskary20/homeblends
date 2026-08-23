(function () {
    function initProductPage() {
        const page = document.querySelector('[data-product-page]');
        if (!page) {
            return;
        }

        const toast = page.querySelector('[data-pdp-toast]');
        const qtyInput = document.getElementById('product-qty');
        const variantSelect = document.getElementById('variant-select');
        const inStock = page.dataset.inStock === '1';

        function cartHeaders() {
            if (typeof window.shopGuestFetchHeaders === 'function') {
                return window.shopGuestFetchHeaders({
                    'Content-Type': 'application/json',
                });
            }

            const headers = {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.shopCsrfToken?.() || document.querySelector('meta[name="csrf-token"]')?.content || '',
            };

            const sessionId = page.dataset.sessionId || window.shopSessionId?.() || '';
            if (sessionId) {
                headers['X-Shop-Session-Id'] = sessionId;
            }

            const token = typeof window.shopAuthToken === 'function' ? window.shopAuthToken() : null;
            if (token) {
                headers.Authorization = `Bearer ${token}`;
            }

            return headers;
        }

        function cartAddUrl() {
            return page.dataset.cartAddUrl
                || document.body?.dataset.cartAddUrl
                || `${page.dataset.api}/cart/items`;
        }

        function showToast(message) {
            if (!toast) {
                return;
            }
            toast.textContent = message;
            toast.classList.add('is-visible');
            clearTimeout(showToast._timer);
            showToast._timer = setTimeout(() => toast.classList.remove('is-visible'), 2800);
        }

        function readQty() {
            const min = parseInt(qtyInput?.min || '1', 10);
            const max = parseInt(qtyInput?.max || '9999', 10);
            const value = parseInt(qtyInput?.value || '1', 10);

            return Math.min(max, Math.max(min, Number.isFinite(value) ? value : 1));
        }

        function writeQty(value) {
            if (!qtyInput) {
                return;
            }
            qtyInput.value = String(value);
        }

        page.querySelectorAll('[data-qty-minus]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                writeQty(Math.max(parseInt(qtyInput?.min || '1', 10), readQty() - 1));
            });
        });

        page.querySelectorAll('[data-qty-plus]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                writeQty(Math.min(parseInt(qtyInput?.max || '9999', 10), readQty() + 1));
            });
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
            if (!btn || btn.disabled || !inStock) {
                return;
            }

            const productId = parseInt(page.dataset.productId, 10);
            const body = {
                product_id: productId,
                quantity: readQty(),
            };

            if (variantSelect?.value) {
                body.product_variant_id = parseInt(variantSelect.value, 10);
            }

            const originalHtml = btn.innerHTML;

            page.querySelectorAll('[data-pdp-add-cart]').forEach((b) => {
                b.classList.add('is-loading');
                b.disabled = true;
            });

            try {
                const res = await fetch(cartAddUrl(), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: cartHeaders(),
                    body: JSON.stringify(body),
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    showToast(data.message || page.dataset.errorLabel || 'تعذّرت الإضافة');
                    return;
                }

                const count = data.totals?.items_count ?? 0;
                window.dispatchEvent(new CustomEvent('cart:updated', { detail: { count } }));

                if (typeof window.scheduleMiniCartRefresh === 'function') {
                    window.scheduleMiniCartRefresh(count);
                } else if (typeof window.refreshMiniCart === 'function') {
                    window.refreshMiniCart(count);
                }

                page.querySelectorAll('[data-pdp-add-cart]').forEach((b) => {
                    b.classList.add('is-success');
                    if (b.classList.contains('hb-pdp-btn-cart') && b.querySelector('svg')) {
                        b.innerHTML = '✓ ' + (page.dataset.addedLabel || 'تمت الإضافة');
                    }
                });

                showToast(page.dataset.addedLabel || 'تمت الإضافة إلى السلة');

                setTimeout(() => {
                    page.querySelectorAll('[data-pdp-add-cart]').forEach((b) => {
                        b.classList.remove('is-success');
                        if (b.classList.contains('hb-pdp-btn-cart') && b.querySelector('svg')) {
                            b.innerHTML = originalHtml;
                        }
                    });
                }, 2200);
            } catch {
                showToast(page.dataset.errorLabel || 'تعذّرت الإضافة');
            } finally {
                page.querySelectorAll('[data-pdp-add-cart]').forEach((b) => {
                    b.classList.remove('is-loading');
                    b.disabled = !inStock;
                });
            }
        }

        page.querySelectorAll('[data-pdp-add-cart]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                addToCart(btn);
            });
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
                        t.classList.toggle('is-active', t === btn);
                        t.classList.toggle('border-amber-600', t === btn);
                        t.classList.toggle('border-transparent', t !== btn);
                    });
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProductPage);
    } else {
        initProductPage();
    }
})();
