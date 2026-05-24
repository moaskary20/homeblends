const app = document.getElementById('cart-app');

function updateCartBadge(count) {
    document.querySelectorAll('[data-cart-count]').forEach((el) => {
        el.textContent = count > 99 ? '99+' : String(count);
        el.classList.toggle('hidden', count < 1);
        el.classList.toggle('hb-cart-hidden', count < 1);
    });
    window.dispatchEvent(new CustomEvent('cart:updated', { detail: { count } }));
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

function normalizeCartItems(cart) {
    if (!cart?.items) {
        return [];
    }

    const raw = cart.items;

    if (Array.isArray(raw)) {
        return raw;
    }

    if (Array.isArray(raw.data)) {
        return raw.data;
    }

    return [];
}

function setCartViewState(hasItems, { loading = false } = {}) {
    document.getElementById('cart-loading')?.classList.toggle('hb-cart-hidden', !loading);
    document.getElementById('cart-empty')?.classList.toggle('hb-cart-hidden', hasItems || loading);
    document.getElementById('cart-items')?.classList.toggle('hb-cart-hidden', !hasItems || loading);
    document.getElementById('cart-sidebar')?.classList.toggle('hb-cart-hidden', !hasItems || loading);
}

if (app) {
    const apiBase = app.dataset.api;
    const token = localStorage.getItem('api_token');
    const initialHasItems = app.dataset.hasItems === '1';

    const headers = () => ({
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
    });

    const fetchOptions = (extra = {}) => ({
        credentials: 'same-origin',
        headers: headers(),
        ...extra,
    });

    const productImage = (item) => {
        const p = item.product;
        if (!p) return null;
        if (p.main_image) return p.main_image;
        if (Array.isArray(p.images) && p.images.length) {
            return p.images[0].url || p.images[0].path;
        }
        return null;
    };

    const productUrlTemplate = app.dataset.productUrlTemplate || '';
    const bundlesUrl = app.dataset.bundlesUrl || '/bundles';

    const productUrl = (item) => {
        if (item.is_bundle) return bundlesUrl;
        const slug = item.product?.slug;
        if (!slug || !productUrlTemplate) return bundlesUrl;
        return productUrlTemplate.replace('__SLUG__', slug);
    };

    const renderLine = (item) => {
        const isBundle = item.is_bundle;
        const title = isBundle
            ? (item.bundle?.name || item.product?.name || 'باقة')
            : (item.product?.name || '');
        const img = productImage(item);
        const url = productUrl(item);
        const badge = isBundle
            ? '<span class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded">باقة</span> '
            : '';
        const included = isBundle && Array.isArray(item.bundle?.items) && item.bundle.items.length
            ? `<ul class="text-sm text-gray-500 mt-2 list-disc list-inside">${item.bundle.items.map((row) =>
                `<li>${escapeHtml(row.product_name || '')} × ${row.quantity || 1}</li>`).join('')}</ul>`
            : '';
        const variantSku = !isBundle && item.product?.sku
            ? `<p class="text-sm text-gray-500 mt-1">${escapeHtml(item.product.sku)}</p>`
            : '';

        return `
            <div class="hb-cart-line p-4 flex flex-wrap gap-4 border-b border-gray-100 last:border-0" data-cart-line data-id="${item.id}">
                <a href="${url}" class="hb-cart-line-image shrink-0">
                    ${img
                        ? `<img src="${escapeHtml(img)}" alt="${escapeHtml(title)}" loading="lazy">`
                        : '<span class="hb-cart-line-placeholder">🛒</span>'}
                </a>
                <div class="flex-1 min-w-[200px]">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            ${badge}
                            <a href="${url}" class="font-semibold text-[#3d3830] hover:text-amber-700 block mt-1">${escapeHtml(title)}</a>
                            ${included}
                            ${variantSku}
                        </div>
                        <p class="font-bold text-amber-800 whitespace-nowrap" data-line-subtotal>
                            ${Number(item.subtotal).toFixed(2)} ج.م
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mt-3">
                        <label class="text-sm text-gray-500">الكمية</label>
                        <input type="number" min="0" max="99" value="${item.quantity}"
                               class="qty-input border border-gray-200 rounded-lg w-20 px-2 py-1 text-center"
                               data-id="${item.id}">
                        <button type="button" class="remove-btn text-red-600 text-sm hover:underline" data-id="${item.id}">حذف</button>
                    </div>
                </div>
            </div>
        `;
    };

    const bindCartLineEvents = (container) => {
        if (!container) return;

        container.querySelectorAll('.qty-input').forEach((input) => {
            input.addEventListener('change', () => updateQty(input.dataset.id, input.value));
        });
        container.querySelectorAll('.remove-btn').forEach((btn) => {
            btn.addEventListener('click', () => updateQty(btn.dataset.id, 0));
        });
    };

    const renderCart = (data) => {
        const items = normalizeCartItems(data.cart);
        const container = document.getElementById('cart-items');
        const itemsCount = data.totals?.items_count ?? items.reduce((sum, item) => sum + (item.quantity || 0), 0);
        const hasItems = items.length > 0;

        updateCartBadge(itemsCount);
        setCartViewState(hasItems);

        if (!hasItems) {
            if (container) {
                container.innerHTML = '';
            }
            return;
        }

        if (container) {
            container.innerHTML = items.map(renderLine).join('');
            bindCartLineEvents(container);
        }

        const subtotalEl = document.getElementById('cart-subtotal');
        if (subtotalEl) {
            subtotalEl.textContent = `${Number(data.totals?.subtotal || 0).toFixed(2)} ج.م`;
        }
        const countEl = document.getElementById('summary-items-count');
        if (countEl) {
            countEl.textContent = String(itemsCount);
        }
    };

    const loadCart = async () => {
        if (!initialHasItems) {
            setCartViewState(false, { loading: true });
        }

        try {
            const res = await fetch(`${apiBase}/cart`, fetchOptions());
            if (!res.ok) {
                setCartViewState(initialHasItems);
                bindCartLineEvents(document.getElementById('cart-items'));
                return;
            }
            const data = await res.json();
            renderCart(data);
        } catch {
            setCartViewState(initialHasItems);
            bindCartLineEvents(document.getElementById('cart-items'));
        }
    };

    const updateQty = async (id, qty) => {
        const quantity = parseInt(qty, 10);
        await fetch(`${apiBase}/cart/items/${id}`, fetchOptions({
            method: quantity > 0 ? 'PATCH' : 'DELETE',
            ...(quantity > 0 ? { body: JSON.stringify({ quantity }) } : {}),
        }));
        await loadCart();
    };

    const showCouponMessage = (text, ok) => {
        const el = document.getElementById('coupon-message');
        if (!el) return;
        el.textContent = text;
        el.classList.remove('hidden', 'bg-green-50', 'text-green-800', 'bg-red-50', 'text-red-700');
        el.classList.add(ok ? 'bg-green-50' : 'bg-red-50', ok ? 'text-green-800' : 'text-red-700');
    };

    document.getElementById('apply-coupon')?.addEventListener('click', async () => {
        const code = document.getElementById('coupon-code')?.value?.trim();
        if (!code) return;
        const res = await fetch(`${apiBase}/cart/coupon`, fetchOptions({
            method: 'POST',
            body: JSON.stringify({ code }),
        }));
        const data = await res.json();
        if (res.ok) {
            showCouponMessage(data.message || 'تم تطبيق الكوبون', true);
            loadCart();
        } else {
            showCouponMessage(data.message || 'كوبون غير صالح', false);
        }
    });

    document.getElementById('save-later')?.addEventListener('click', async () => {
        const res = await fetch(`${apiBase}/cart/save-for-later`, fetchOptions({
            method: 'POST',
        }));
        if (res.ok) {
            showCouponMessage('تم حفظ السلة', true);
            loadCart();
        }
    });

    updateCartBadge(parseInt(app.dataset.initialCount || '0', 10));
    bindCartLineEvents(document.getElementById('cart-items'));
    loadCart();
}
