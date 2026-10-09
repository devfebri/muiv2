/**
 * Utilitas bersama: HTTP helper (CSRF) & format pesan aman.
 */
export const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export async function http(url, { method = 'GET', body, headers = {} } = {}) {
    const res = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf(),
            ...(body && !(body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
            ...headers,
        },
        body: body instanceof FormData ? body : body ? JSON.stringify(body) : undefined,
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok || data?.success === false) {
        const message = Object.values(data?.errors ?? {})?.[0]?.[0] || data?.error || data?.message || 'Terjadi kesalahan. Silakan coba lagi.';
        throw Object.assign(new Error(message), { status: res.status, data });
    }
    return data;
}

export const escapeHtml = (s = '') =>
    String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

/** Teks → HTML aman dengan tautan otomatis, huruf tebal (**teks**) & baris baru. */
export const formatMessage = (s = '') =>
    escapeHtml(s)
        .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener" class="underline">$1</a>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>');
