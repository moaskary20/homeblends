const form = document.getElementById('checkout-form');
let loyaltyInfo = null;

const isWebAuthenticated = () => document.body?.dataset.userAuthenticated === '1';

const buildHeaders = (token) => {
    const headers = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': window.shopCsrfToken?.() || document.querySelector('meta[name="csrf-token"]')?.content || '',
    };

    const sessionId = form?.dataset.sessionId || window.shopSessionId?.() || '';
    if (sessionId) {
        headers['X-Shop-Session-Id'] = sessionId;
    }

    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    return headers;
};

const loadLoyalty = async (token) => {
    const section = document.getElementById('loyalty-section');
    if (!section || !token) {
        return;
    }

    const res = await fetch(`${form.dataset.api}/loyalty/balance`, {
        headers: buildHeaders(token),
        credentials: 'same-origin',
    });

    if (!res.ok) {
        return;
    }

    loyaltyInfo = await res.json();
    section.classList.remove('hidden');

    document.getElementById('loyalty-balance').textContent =
        `${__('balance')}: ${loyaltyInfo.points} نقطة (الحد الأقصى للاستبدال: حسب السلة)`;

    if (loyaltyInfo.vip_level && loyaltyInfo.vip_discount_percent > 0) {
        const vip = document.getElementById('loyalty-vip');
        vip.textContent = `مستوى ${loyaltyInfo.vip_level.name} — خصم VIP ${loyaltyInfo.vip_discount_percent}%`;
        vip.classList.remove('hidden');
    }

    const input = document.getElementById('loyalty_points');
    input?.addEventListener('input', () => previewLoyalty(token));
};

const previewLoyalty = async (token) => {
    const points = parseInt(document.getElementById('loyalty_points')?.value || '0', 10);
    const preview = document.getElementById('loyalty-discount-preview');

    if (!points) {
        preview.textContent = '0.00 ج.م';
        return;
    }

    const res = await fetch(`${form.dataset.api}/loyalty/preview`, {
        method: 'POST',
        headers: buildHeaders(token),
        credentials: 'same-origin',
        body: JSON.stringify({ points }),
    });

    const data = await res.json();
    preview.textContent = `${Number(data.discount_value || 0).toFixed(2)} ج.م`;
    if (data.message) {
        preview.title = data.message;
    }
};

function __(key) {
    return key === 'balance' ? 'رصيدك' : key;
}

function redirectToLogin() {
    const loginUrl = form.dataset.loginUrl || '/login';
    const redirect = encodeURIComponent(window.location.href);
    window.location.href = `${loginUrl}?redirect=${redirect}`;
}

function firstValidationMessage(data) {
    if (data?.message) {
        return data.message;
    }

    const errors = data?.errors;
    if (!errors || typeof errors !== 'object') {
        return null;
    }

    const first = Object.values(errors).flat()[0];
    return typeof first === 'string' ? first : null;
}

if (form) {
    const token = localStorage.getItem('api_token');
    if (token) {
        loadLoyalty(token);
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const fd = new FormData(form);
        const shippingAddress = {
            first_name: fd.get('first_name'),
            last_name: fd.get('last_name'),
            email: fd.get('email'),
            phone: fd.get('phone'),
            alternate_phone: fd.get('alternate_phone') || null,
            address_line_1: fd.get('address_line_1'),
            address_line_2: fd.get('address_line_2') || null,
            city: fd.get('city'),
            state: fd.get('state') || null,
            postal_code: fd.get('postal_code') || null,
            country: fd.get('country'),
        };

        const loyaltyPoints = parseInt(fd.get('loyalty_points') || '0', 10);
        const authToken = localStorage.getItem('api_token');
        const checkoutUrl = form.dataset.checkoutUrl || `${form.dataset.api}/checkout`;
        const payload = {
            shipping_address: shippingAddress,
            billing_address: shippingAddress,
            shipping_rate_id: parseInt(fd.get('shipping_rate_id'), 10),
            payment_gateway: fd.get('payment_gateway'),
            loyalty_points: loyaltyPoints,
            notes: fd.get('notes'),
            pay_in_installments: fd.get('pay_in_installments') === '1',
        };

        if (!isWebAuthenticated() && fd.get('password')) {
            payload.password = fd.get('password');
        }

        const res = await fetch(checkoutUrl, {
            method: 'POST',
            headers: buildHeaders(authToken),
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });

        const data = await res.json().catch(() => ({}));
        const err = document.getElementById('checkout-error');

        if (res.status === 401) {
            alert(form.dataset.loginRequired || 'يجب تسجيل الدخول أولاً');
            redirectToLogin();
            return;
        }

        if (res.ok) {
            if (document.body) {
                document.body.dataset.userAuthenticated = '1';
            }
            const payloadData = data.data ?? data;
            const earned = payloadData.loyalty_points_earned;
            let msg = `تم إنشاء الطلب: ${payloadData.order_number || 'نجاح'}`;
            if (earned) {
                msg += `\nستحصل على ${earned} نقطة ولاء`;
            }
            alert(msg);
            window.location.href = payloadData.order_number && form.dataset.orderUrlTemplate
                ? form.dataset.orderUrlTemplate.replace('__ORDER__', payloadData.order_number)
                : (form.dataset.ordersUrl || '/');
        } else {
            err.textContent = firstValidationMessage(data) || 'فشل إتمام الطلب';
            err.classList.remove('hidden');
        }
    });

    const installmentBox = document.getElementById('pay_in_installments');
    const installmentSchedule = document.getElementById('installment-schedule');
    const syncInstallment = () => {
        installmentSchedule?.classList.toggle('hidden', !installmentBox?.checked);
    };
    installmentBox?.addEventListener('change', syncInstallment);
    syncInstallment();
}
