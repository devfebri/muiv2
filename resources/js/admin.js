import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { http, escapeHtml, formatMessage } from './bootstrap';

window.Alpine = Alpine;
Alpine.plugin(collapse);

/* ------------------------------------------------------------------
 | Utilitas global untuk skrip halaman
 * ------------------------------------------------------------------ */
const toast = (message, type = 'success') => window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));

let confirmResolver = null;
const confirmAction = ({ title = 'Konfirmasi', message = 'Lanjutkan tindakan ini?', confirmText = 'Ya, lanjutkan', tone = 'danger' } = {}) => new Promise((resolve) => {
    confirmResolver = resolve;
    window.dispatchEvent(new CustomEvent('confirm-open', { detail: { title, message, confirmText, tone } }));
});

const reloadTables = () => window.dispatchEvent(new CustomEvent('table:reload'));

/** Hapus data melalui AJAX (DELETE) dengan dialog konfirmasi. */
async function destroy(url, { title = 'Hapus data?', message = 'Data yang dihapus tidak dapat dikembalikan.' } = {}) {
    if (!(await confirmAction({ title, message, confirmText: 'Ya, hapus' }))) return false;
    try {
        const res = await http(url, { method: 'DELETE' });
        toast(res.message || 'Data berhasil dihapus.');
        reloadTables();
        return res;
    } catch (e) {
        toast(e.message, 'error');
        return false;
    }
}

const formatDate = (value, opts = { day: '2-digit', month: 'short', year: 'numeric' }) => {
    if (!value) return '—';
    const d = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString('id-ID', opts);
};

window.MUIAdmin = { http, toast, confirmAction, destroy, reloadTables, esc: escapeHtml, formatMessage, formatDate };

/* ------------------------------------------------------------------
 | Tata letak admin: sidebar & notifikasi
 * ------------------------------------------------------------------ */
Alpine.data('adminShell', (pollUrl = null) => ({
    sidebar: false,
    collapsed: (() => { try { return localStorage.getItem('admin_sidebar_collapsed') === '1'; } catch { return false; } })(),
    notif: { total: 0, chat_count: 0, konsultasi_count: 0, items: [] },
    lastChatId: undefined,
    pausedUntil: 0,
    init() {
        this.$watch('collapsed', (v) => { try { localStorage.setItem('admin_sidebar_collapsed', v ? '1' : '0'); } catch { /* abaikan */ } });
        this.$watch('sidebar', (v) => document.documentElement.classList.toggle('overflow-hidden', v));
        // Jangan polling saat formulir sedang dikirim: permintaan yang bersamaan bisa menimpa
        // sesi (pesan flash "berhasil disimpan" hilang).
        document.addEventListener('submit', () => { this.pausedUntil = Date.now() + 8000; }, true);
        if (pollUrl) {
            this.poll(true);
            setInterval(() => this.poll(), 20000);
        }
    },
    async poll(force = false) {
        if (!force && (document.hidden || Date.now() < this.pausedUntil)) return;
        try {
            const res = await http(pollUrl);
            // Halaman live chat punya notifier sendiri (#lc-feed); hindari notifikasi ganda di sana.
            const pageHasOwnNotifier = !!document.getElementById('lc-feed');
            if (!pageHasOwnNotifier && this.lastChatId !== undefined && res.latest_chat_id && res.latest_chat_id !== this.lastChatId) {
                toast('Ada pengunjung baru menunggu di antrian live chat.', 'info');
                this.chime();
            }
            this.lastChatId = res.latest_chat_id ?? null;
            this.notif = res;
        } catch { /* abaikan */ }
    },
    chime() {
        try {
            const ctx = new AudioContext();
            const o = ctx.createOscillator();
            const g = ctx.createGain();
            o.connect(g);
            g.connect(ctx.destination);
            o.frequency.value = 880;
            g.gain.setValueAtTime(0.08, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.5);
            o.start();
            o.stop(ctx.currentTime + 0.5);
        } catch { /* abaikan */ }
    },
}));

Alpine.data('clock', () => ({
    text: '',
    init() {
        const tick = () => {
            const now = new Date();
            this.text = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' }) + ' · ' + now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');
        };
        tick();
        setInterval(tick, 30000);
    },
}));

/* ------------------------------------------------------------------
 | Toast
 * ------------------------------------------------------------------ */
Alpine.data('toasts', (initial = []) => ({
    items: [],
    init() {
        initial.forEach((t) => this.add(t));
        window.addEventListener('toast', (e) => this.add(e.detail));
    },
    add({ type = 'success', message }) {
        if (!message) return;
        const id = Date.now() + Math.random();
        this.items.push({ id, type, message });
        setTimeout(() => this.remove(id), 6000);
    },
    remove(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
}));

/* ------------------------------------------------------------------
 | Dialog konfirmasi (form [data-confirm] maupun MUIAdmin.confirmAction)
 * ------------------------------------------------------------------ */
Alpine.data('confirmDialog', () => ({
    open: false,
    title: '',
    message: '',
    confirmText: 'Ya, lanjutkan',
    tone: 'danger',
    form: null,
    init() {
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.dataset.confirm && !form.dataset.confirmed) {
                e.preventDefault();
                this.form = form;
                this.show({ title: form.dataset.confirmTitle || 'Konfirmasi', message: form.dataset.confirm, confirmText: form.dataset.confirmButton || 'Ya, lanjutkan', tone: form.dataset.confirmTone || 'danger' });
            }
        });
        window.addEventListener('confirm-open', (e) => {
            this.form = null;
            this.show(e.detail);
        });
    },
    show({ title, message, confirmText = 'Ya, lanjutkan', tone = 'danger' }) {
        Object.assign(this, { title, message, confirmText, tone, open: true });
        this.$nextTick(() => this.$refs.confirm?.focus());
    },
    proceed() {
        this.open = false;
        if (this.form) {
            this.form.dataset.confirmed = '1';
            this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit();
        } else if (confirmResolver) {
            confirmResolver(true);
            confirmResolver = null;
        }
    },
    cancel() {
        this.open = false;
        if (confirmResolver) {
            confirmResolver(false);
            confirmResolver = null;
        }
    },
}));

/* ------------------------------------------------------------------
 | Tabel server-side (kompatibel dengan protokol DataTables yang
 | sudah dipakai controller: draw, start, length, search[value], order).
 * ------------------------------------------------------------------ */
Alpine.data('serverTable', (config = {}) => ({
    url: config.url,
    columns: config.columns ?? [],
    sort: { col: config.order?.[0] ?? 0, dir: config.order?.[1] ?? 'desc' },
    perPage: config.perPage ?? 10,
    filters: { ...(config.filters ?? {}) },
    search: '',
    page: 1,
    rows: [],
    total: 0,
    filtered: 0,
    loading: true,
    error: null,
    meta: {},
    draw: 0,
    init() {
        let timer;
        this.$watch('search', () => {
            clearTimeout(timer);
            timer = setTimeout(() => { this.page = 1; this.load(); }, 350);
        });
        this.$watch('perPage', () => { this.page = 1; this.load(); });
        this.$watch('filters', () => { this.page = 1; this.load(); });
        window.addEventListener('table:reload', () => this.load());
        this.load();
    },
    params() {
        const q = new URLSearchParams();
        q.set('draw', String(++this.draw));
        q.set('start', String((this.page - 1) * this.perPage));
        q.set('length', String(this.perPage));
        q.set('search[value]', this.search.trim());
        q.set('search[regex]', 'false');
        this.columns.forEach((name, i) => {
            q.set(`columns[${i}][data]`, name);
            q.set(`columns[${i}][name]`, name);
            q.set(`columns[${i}][searchable]`, 'true');
            q.set(`columns[${i}][orderable]`, 'true');
        });
        q.set('order[0][column]', String(this.sort.col));
        q.set('order[0][dir]', this.sort.dir);
        Object.entries(this.filters).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) q.set(key, value);
        });
        return q;
    },
    async load() {
        this.loading = true;
        this.error = null;
        const q = this.params();
        const draw = Number(q.get('draw'));
        try {
            const res = await http(`${this.url}${this.url.includes('?') ? '&' : '?'}${q}`);
            if (draw !== this.draw) return;
            this.rows = res.data ?? [];
            this.total = res.recordsTotal ?? this.rows.length;
            this.filtered = res.recordsFiltered ?? this.total;
            this.meta = res;
            if (this.page > this.pages) this.page = this.pages;
        } catch (e) {
            this.error = e.message;
            this.rows = [];
        } finally {
            if (draw === this.draw) this.loading = false;
        }
    },
    sortBy(col) {
        this.sort = this.sort.col === col ? { col, dir: this.sort.dir === 'asc' ? 'desc' : 'asc' } : { col, dir: 'asc' };
        this.load();
    },
    sortIcon(col) {
        return this.sort.col !== col ? 'none' : this.sort.dir;
    },
    reset() {
        this.search = '';
        this.filters = { ...(config.filters ?? {}) };
    },
    get pages() {
        return Math.max(1, Math.ceil(this.filtered / this.perPage));
    },
    get from() {
        return this.filtered ? (this.page - 1) * this.perPage + 1 : 0;
    },
    get to() {
        return Math.min(this.page * this.perPage, this.filtered);
    },
    get pageList() {
        const total = this.pages;
        const cur = this.page;
        const set = new Set([1, total, cur - 1, cur, cur + 1].filter((p) => p >= 1 && p <= total));
        const sorted = [...set].sort((a, b) => a - b);
        const out = [];
        sorted.forEach((p, i) => {
            if (i && p - sorted[i - 1] > 1) out.push('…');
            out.push(p);
        });
        return out;
    },
    go(p) {
        if (p === '…') return;
        const next = Math.min(Math.max(1, p), this.pages);
        if (next === this.page) return;
        this.page = next;
        this.load();
    },
    rowNumber(index) {
        return (this.page - 1) * this.perPage + index + 1;
    },
}));

/* ------------------------------------------------------------------
 | Formulir modal tambah / ubah (AJAX, mendukung unggah berkas)
 * ------------------------------------------------------------------ */
Alpine.data('crudForm', (config = {}) => ({
    open: false,
    mode: 'create',
    saving: false,
    errors: {},
    defaults: { ...(config.defaults ?? {}) },
    data: { ...(config.defaults ?? {}) },
    storeUrl: config.storeUrl,
    updateUrl: config.updateUrl,
    init() {
        window.addEventListener(`${config.name ?? 'crud'}:open`, (e) => this.show(e.detail ?? null));
    },
    show(record = null) {
        // $dispatch tanpa detail mengirim objek kosong; anggap "ubah" hanya bila ada id.
        const editing = !!(record && record.id !== undefined && record.id !== null);
        this.errors = {};
        this.mode = editing ? 'edit' : 'create';
        this.data = editing ? { ...this.defaults, ...record } : { ...this.defaults };
        this.open = true;
        this.$nextTick(() => {
            this.$refs.form?.querySelectorAll('input[type=file]').forEach((f) => { f.value = ''; });
            this.$refs.first?.focus();
        });
    },
    close() {
        this.open = false;
    },
    error(field) {
        return this.errors?.[field]?.[0] ?? null;
    },
    async submit() {
        if (this.saving) return;
        this.saving = true;
        this.errors = {};
        const fd = new FormData(this.$refs.form);
        let url = this.storeUrl;
        if (this.mode === 'edit') {
            url = this.updateUrl.replace(':id', this.data.id);
            fd.append('_method', config.updateMethod ?? 'PUT');
        }
        try {
            const res = await http(url, { method: 'POST', body: fd });
            toast(res.message || 'Data berhasil disimpan.');
            this.open = false;
            reloadTables();
            window.dispatchEvent(new CustomEvent(`${config.name ?? 'crud'}:saved`, { detail: res }));
        } catch (e) {
            this.errors = e.data?.errors ?? {};
            toast(e.message, 'error');
        } finally {
            this.saving = false;
        }
    },
}));

/* ------------------------------------------------------------------
 | Pratinjau unggahan berkas / gambar
 * ------------------------------------------------------------------ */
Alpine.data('filePicker', (initialUrl = null) => ({
    preview: initialUrl,
    name: null,
    size: null,
    dragging: false,
    pick(e) {
        this.set(e.target.files?.[0]);
    },
    drop(e) {
        this.dragging = false;
        const file = e.dataTransfer.files?.[0];
        if (!file) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        this.$refs.file.files = dt.files;
        this.$refs.file.dispatchEvent(new Event('change', { bubbles: true }));
    },
    set(file) {
        if (!file) return;
        this.name = file.name;
        this.size = file.size > 1048576 ? `${(file.size / 1048576).toFixed(1)} MB` : `${Math.ceil(file.size / 1024)} KB`;
        this.preview = file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
    },
    clear() {
        this.preview = null;
        this.name = null;
        this.size = null;
        if (this.$refs.file) this.$refs.file.value = '';
    },
}));

Alpine.start();
