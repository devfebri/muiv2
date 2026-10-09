<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Kategori extends Model
{
    /** @var array<string> */
    protected $fillable = [
        'nama',
        'slug',
        'warna',
        'deskripsi',
        'aktif',
        'urutan',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'aktif' => 'boolean',
        'urutan' => 'integer',
    ];

    /* ── Auto-slug ── */
    public function setNamaAttribute(string $value): void
    {
        $this->attributes['nama'] = $value;
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    /* ── Relationships ── */
    public function beritas(): HasMany
    {
        return $this->hasMany(Berita::class, 'kategori', 'nama');
    }
}
