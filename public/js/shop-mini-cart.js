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

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

function productImage(item) {
    const p = item.product;
    if (!p) return null;
    if (p.main_image) return p.main_image;
    if (Array.isArray(p.images) && p.images.length) {
        return p.images[0].url || p.images[0].path;
    }
    return null;
}

function productUrl(item, root) {
    if (item.is_bundle) {
        return root.dataset.bundlesUrl || '/bundles';
    }
    const slug = item.product?.slug;
    const template = root.dataset.productUrlTemplate || '';
    if (!slug || !template) {
        return root.dataset.bundlesUrl || '#';
    }
    return template.replace('__SLUG__', slug);
}

function renderMiniCartItem(item, root) {
    const currency = root.dataset.currency || 'ج.م';
    const title = item.is_bundle
        ? (item.bundle?.name || item.product?.name || 'باقة')
        : (item.product?.name || '');
    const img = productImage(item);
    const url = productUrl(item, root);

    return `
        <li class="hb-mini-cart-item">
            <a href="${url}" class="hb-mini-cart-item-thumb">
                ${img
                    ? `<img src="${escapeHtml(img)}" alt="${escapeHtml(title)}" loading="lazy" width="48" height="48">`
                    : '<span class="hb-mini-cart-item-placeholder">🛒</span>'}
            </a>
            <div class="hb-mini-cart-item-info">
                <a href="${url}" class="hb-mini-cart-item-title">${escapeHtml(title)}</a>
                <span class="hb-mini-cart-item-meta">${item.quantity} × ${Number(item.unit_price).toFixed(2)} ${currency}</span>
            </div>
            <span class="hb-mini-cart-item-price">${Number(item.subtotal).toFixed(2)}</span>
        </li>
    `;
}

async function refreshMiniCart() {
    const root = document.querySelector('[data-mini-cart]');
    const body = root?.querySelector('[data-mini-cart-body]');
    if (!root || !body) {
        return;
    }

    const apiBase = root.dataset.api;
    const sessionId = root.dataset.sessionId;
    const token = localStorage.getItem('api_token');
    const currency = root.dataset.currency || 'ج.م';

    try {
        const res = await fetch(`${apiBase}/cart`, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Session-Id': sessionId,
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
            },
        });

        if (!res.ok) {
            return;
        }

        const data = await res.json();
        const items = normalizeCartItems(data.cart);
        const itemsCount = data.totals?.items_count ?? 0;
        const subtotal = Number(data.totals?.subtotal || 0);
        const preview = items.slice(0, 5);
        const moreCount = Math.max(0, itemsCount - preview.length);

        document.querySelectorAll('[data-cart-count]').forEach((el) => {
            el.textContent = itemsCount > 99 ? '99+' : String(itemsCount);
            el.classList.toggle('hidden', itemsCount < 1);
            el.classList.toggle('hb-cart-hidden', itemsCount < 1);
        });

        const countEl = root.querySelector('[data-mini-cart-count]');
        if (countEl) {
            countEl.textContent = itemsCount > 0
                ? `${itemsCount} ${root.dataset.itemsLabel || ''}`
                : '';
            countEl.classList.toggle('hb-cart-hidden', itemsCount < 1);
        }

        if (!items.length) {
            body.innerHTML = `
                <div class="hb-mini-cart-empty">
                    <p>${escapeHtml(root.dataset.emptyText)}</p>
                    <a href="${root.dataset.continueUrl}" class="hb-mini-cart-link">${escapeHtml(root.dataset.continueLabel)}</a>
                </div>
            `;
            return;
        }

        const moreHtml = moreCount > 0
            ? `<p class="hb-mini-cart-more text-xs text-gray-500 px-4 py-2 border-t border-gray-100">${escapeHtml((root.dataset.moreTemplate || '').replace(':count', String(moreCount)))}</p>`
            : '';

        body.innerHTML = `
            <ul class="hb-mini-cart-items">${preview.map((item) => renderMiniCartItem(item, root)).join('')}</ul>
            ${moreHtml}
            <div class="hb-mini-cart-footer">
                <div class="hb-mini-cart-subtotal">
                    <span>${escapeHtml(root.dataset.subtotalLabel || '')}</span>
                    <strong data-mini-cart-subtotal>${subtotal.toFixed(2)} ${currency}</strong>
                </div>
                <a href="${root.dataset.cartUrl}" class="hb-mini-cart-btn hb-mini-cart-btn-secondary">${escapeHtml(root.dataset.cartLabel)}</a>
                <a href="${root.dataset.checkoutUrl}" class="hb-mini-cart-btn hb-mini-cart-btn-primary">${escapeHtml(root.dataset.checkoutLabel)}</a>
            </div>
        `;
    } catch {
        //
    }
}

window.refreshMiniCart = refreshMiniCart;
window.addEventListener('cart:updated', () => refreshMiniCart());
