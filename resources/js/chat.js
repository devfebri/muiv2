import { http, formatMessage } from './bootstrap';

/**
 * Widget Live Chat & Konsultasi Online (API: /livechat/*).
 * Alur: perkenalan → antrian petugas (menunggu) → percakapan (bot / aktif) → selesai.
 */
const TOKEN_KEY = 'mui_livechat_token';

const storage = {
    get() {
        try { return localStorage.getItem(TOKEN_KEY); } catch { return null; }
    },
    set(value) {
        try { localStorage.setItem(TOKEN_KEY, value); } catch { /* abaikan */ }
    },
    clear() {
        try { localStorage.removeItem(TOKEN_KEY); } catch { /* abaikan */ }
    },
};

function chime() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.setValueAtTime(587.33, ctx.currentTime);
        osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.12, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.35);
    } catch { /* audio tidak tersedia */ }
}

export default function registerChatWidget(Alpine) {
    Alpine.data('chatWidget', (options = {}) => ({
        embedded: options.embedded ?? false,
        open: options.embedded ?? false,
        routes: window.MUI?.chat ?? {},
        ready: false,
        operational: false,
        scheduleText: '',
        greeting: '',
        offlineText: '',
        faqs: [],
        token: null,
        session: null,
        messages: [],
        lastId: 0,
        form: { nama_pengunjung: '', nohp_pengunjung: '', topik: '', pesan_awal: '' },
        input: '',
        sending: false,
        starting: false,
        error: null,
        unread: 0,
        pollTimer: null,

        async init() {
            this.token = storage.get();
            await this.load();
            window.addEventListener('open-chat', () => this.toggle(true));
            this.$watch('open', (v) => {
                if (v) {
                    this.unread = 0;
                    this.scrollBottom();
                }
                this.schedulePolling();
            });
        },

        destroy() {
            clearTimeout(this.pollTimer);
        },

        get stage() {
            if (!this.session) return 'intro';
            return this.session.status === 'menunggu' ? 'queue' : 'chat';
        },

        get closed() {
            return this.session?.status === 'selesai';
        },

        get statusText() {
            const s = this.session;
            if (!s) return this.operational ? 'Petugas siap melayani' : 'Asisten virtual 24 jam';
            if (s.status === 'bot') return 'Asisten virtual MUI';
            if (s.status === 'menunggu') return `Antrian ke-${s.antrian_position || 1} · estimasi ±${s.estimasi_tunggu || 2} menit`;
            if (s.status === 'aktif') return `Dilayani ${s.operator_name || 'petugas MUI'}`;
            return 'Sesi telah selesai';
        },

        toggle(force = null) {
            this.open = force ?? !this.open;
        },

        async load() {
            try {
                const url = this.routes.init + (this.token ? `?token=${encodeURIComponent(this.token)}` : '');
                const data = await http(url);
                this.operational = !!data.is_operational;
                this.scheduleText = data.schedule_text ?? '';
                this.greeting = data.greeting_text ?? '';
                this.offlineText = data.offline_text ?? '';
                this.faqs = data.faqs ?? [];

                if (data.session) {
                    this.session = data.session;
                    this.messages = [];
                    this.lastId = 0;
                    (data.messages ?? []).forEach((m) => this.push(m));
                } else if (this.token) {
                    this.forget();
                }
            } catch {
                /* layanan sementara tidak tersedia */
            } finally {
                this.ready = true;
                this.schedulePolling();
            }
        },

        async start() {
            this.error = null;
            if (this.form.nama_pengunjung.trim().length < 2) {
                this.error = 'Mohon isi nama Anda.';
                return;
            }
            this.starting = true;
            try {
                const res = await http(this.routes.start, {
                    method: 'POST',
                    body: { ...this.form, is_bot: !this.operational },
                });
                this.begin(res);
                this.form.pesan_awal = '';
            } catch (e) {
                this.error = e.message;
            } finally {
                this.starting = false;
            }
        },

        begin(res) {
            this.token = res.session.token;
            storage.set(this.token);
            this.session = res.session;
            this.messages = [];
            this.lastId = 0;
            (res.messages ?? []).forEach((m) => this.push(m));
            this.schedulePolling();
        },

        async askFaq(faq) {
            if (this.sending) return;
            this.error = null;
            this.sending = true;
            try {
                if (!this.token) {
                    const res = await http(this.routes.start, {
                        method: 'POST',
                        body: { nama_pengunjung: this.form.nama_pengunjung.trim() || 'Pengunjung Web', topik: 'Pertanyaan FAQ', is_bot: true },
                    });
                    this.begin(res);
                }
                this.push({ id: `tmp-${Date.now()}`, sender_type: 'pengunjung', sender_name: 'Saya', pesan: faq.pertanyaan, time: this.now() });
                const res = await http(this.routes.askFaq, { method: 'POST', body: { token: this.token, faq_id: faq.id } });
                if (res.bot_reply) this.push(res.bot_reply);
            } catch (e) {
                this.error = e.message;
            } finally {
                this.sending = false;
            }
        },

        async send() {
            const pesan = this.input.trim();
            if (!pesan || this.sending || this.closed || !this.token) return;
            this.input = '';
            this.$refs.input && (this.$refs.input.style.height = 'auto');
            this.sending = true;
            const tempId = `tmp-${Date.now()}`;
            this.push({ id: tempId, sender_type: 'pengunjung', sender_name: 'Saya', pesan, time: this.now() });
            try {
                const res = await http(this.routes.send, { method: 'POST', body: { token: this.token, pesan } });
                this.replace(tempId, res.message);
                if (res.bot_reply) this.push(res.bot_reply);
            } catch (e) {
                this.messages = this.messages.filter((m) => m.id !== tempId);
                this.input = pesan;
                this.error = e.message;
            } finally {
                this.sending = false;
                this.$nextTick(() => this.$refs.input?.focus());
            }
        },

        async end() {
            if (!this.token || !confirm('Akhiri sesi percakapan ini?')) return;
            try {
                await http(this.routes.close, { method: 'POST', body: { token: this.token } });
            } catch { /* tetap akhiri di sisi pengunjung */ }
            this.reset();
        },

        reset() {
            clearTimeout(this.pollTimer);
            this.forget();
            this.session = null;
            this.messages = [];
            this.lastId = 0;
            this.error = null;
        },

        forget() {
            this.token = null;
            storage.clear();
        },

        schedulePolling() {
            clearTimeout(this.pollTimer);
            if (!this.token || !this.session || this.closed) return;
            const delay = this.session.status === 'bot' ? 10000 : (this.open ? 3000 : 6000);
            this.pollTimer = setTimeout(() => this.poll(), delay);
        },

        async poll() {
            try {
                const url = `${this.routes.poll}?token=${encodeURIComponent(this.token)}&last_id=${this.lastId}`;
                const { messages = [], ...state } = await http(url);
                const before = this.session?.status;
                this.session = { ...this.session, ...state };
                messages.forEach((m) => this.push(m, true));
                if (before === 'menunggu' && state.status === 'aktif') chime();
            } catch (e) {
                if (e.status === 404) {
                    this.reset();
                    return;
                }
            }
            this.schedulePolling();
        },

        push(message, notify = false) {
            if (this.messages.some((m) => m.id === message.id)) return;
            const pending = message.sender_type === 'pengunjung'
                && this.messages.find((m) => String(m.id).startsWith('tmp-') && m.pesan === message.pesan);
            const entry = { ...message, html: formatMessage(message.pesan) };
            if (pending) {
                Object.assign(pending, entry);
            } else {
                this.messages.push(entry);
            }
            if (Number.isInteger(message.id)) this.lastId = Math.max(this.lastId, message.id);
            if (notify && message.sender_type !== 'pengunjung') {
                if (!this.open) this.unread++;
                if (message.sender_type === 'operator') chime();
            }
            this.scrollBottom();
        },

        replace(tempId, message) {
            const entry = this.messages.find((m) => m.id === tempId);
            if (!entry || !message) return;
            if (this.messages.some((m) => m.id === message.id)) {
                this.messages = this.messages.filter((m) => m.id !== tempId);
            } else {
                Object.assign(entry, { ...message, html: formatMessage(message.pesan) });
            }
            if (Number.isInteger(message.id)) this.lastId = Math.max(this.lastId, message.id);
        },

        now() {
            return new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');
        },

        scrollBottom() {
            this.$nextTick(() => {
                const box = this.$refs.messages;
                if (box) box.scrollTo({ top: box.scrollHeight, behavior: 'smooth' });
            });
        },
    }));
}
