<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Berita extends Model
{
    /** @var array<string> */
    protected $fillable = [
        'user_id',
        'judul',
        'slug',
        'kategori',
        'isi',
        'gambar',
        'status',
        'views',
        'published_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'views' => 'integer',
        'published_at' => 'datetime',
    ];

    /* ── Relationships ─────────────────────────────── */

    public function penulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->penulis();
    }

    /* ── Accessors / Mutators ──────────────────────── */

    /**
     * Auto-generate slug from judul when setting judul.
     */
    public function setJudulAttribute(string $value): void
    {
        $this->attributes['judul'] = $value;
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    /**
     * URL publik untuk gambar berita menggunakan asset().
     */
    public function getGambarUrlAttribute(): ?string
    {
        return $this->gambar ? asset('uploads/berita/'.basename($this->gambar)) : null;
    }

    /**
     * Tanggal tayang berita (published_at, atau created_at bila belum diisi).
     */
    public function getTanggalTerbitAttribute(): ?Carbon
    {
        return $this->published_at ?? $this->created_at;
    }

    /**
     * Ringkasan teks polos dari isi berita.
     */
    public function ringkasan(int $limit = 150): string
    {
        $html = (string) preg_replace('#<(br|/p|/div|/li|/h[1-6]|/blockquote|/tr)\b[^>]*>#i', '$0 ', (string) $this->isi);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::limit(trim((string) preg_replace('/\s+/u', ' ', $text)), $limit);
    }

    /**
     * Perkiraan waktu baca dalam menit (±200 kata per menit).
     */
    public function waktuBaca(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->isi)) / 200));
    }
}
