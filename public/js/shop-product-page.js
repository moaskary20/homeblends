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

        function parseBound(value, fallback) {
            const parsed = parseInt(String(value ?? ''), 10);

            return Number.isFinite(parsed) ? parsed : fallback;
        }

        function getMaxQty() {
            const selectedVariant = variantSelect?.selectedOptions?.[0];
            if (selectedVariant) {
                const variantStock = parseBound(selectedVariant.dataset.stock, NaN);
                if (Number.isFinite(variantStock) && variantStock > 0) {
                    return variantStock;
                }
            }

            const inputMax = parseBound(qtyInput?.max, NaN);
            if (Number.isFinite(inputMax) && inputMax > 0) {
                return inputMax;
            }

            return Math.max(1, parseBound(page.dataset.defaultMaxQty, 99));
        }

        function readQty() {
            const min = parseBound(qtyInput?.min, 1);
            const max = getMaxQty();
            const value = parseBound(qtyInput?.value, 1);

            return Math.min(max, Math.max(min, value));
        }

        function updateQtyButtons() {
            const min = parseBound(qtyInput?.min, 1);
            const max = getMaxQty();
            const current = readQty();

            page.querySelectorAll('[data-qty-minus]').forEach((btn) => {
                btn.disabled = current <= min;
            });
            page.querySelectorAll('[data-qty-plus]').forEach((btn) => {
                btn.disabled = current >= max;
            });
        }

        function writeQty(value) {
            if (!qtyInput) {
                return;
            }

            const min = parseBound(qtyInput.min, 1);
            const max = getMaxQty();
            const next = Math.min(max, Math.max(min, value));

            qtyInput.max = String(max);
            qtyInput.value = String(next);
            qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
            updateQtyButtons();
        }

        function adjustQty(delta) {
            writeQty(readQty() + delta);
        }

        const qtyControl = page.querySelector('.hb-pdp-qty-control');
        if (qtyControl) {
            qtyControl.addEventListener('click', (event) => {
                const minus = event.target.closest('[data-qty-minus]');
                const plus = event.target.closest('[data-qty-plus]');

                if (!minus && !plus) {
                    return;
                }

                event.preventDefault();
                adjustQty(minus ? -1 : 1);
            });
        }

        qtyInput?.addEventListener('input', () => {
            writeQty(readQty());
        });

        qtyInput?.addEventListener('blur', () => {
            writeQty(readQty());
        });

        variantSelect?.addEventListener('change', () => {
            writeQty(readQty());
        });

        writeQty(readQty());

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
