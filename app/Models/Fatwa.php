<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fatwa extends Model
{
    public const STATUS_AKTIF = 'aktif';

    public const STATUS_DIREVISI = 'direvisi';

    public const STATUS_DIGANTIKAN = 'digantikan';

    public const STATUSES = [
        self::STATUS_AKTIF => 'Aktif',
        self::STATUS_DIREVISI => 'Direvisi',
        self::STATUS_DIGANTIKAN => 'Digantikan',
    ];

    /** @var array<string> */
    protected $fillable = [
        'kategori_fatwa_id',
        'judul',
        'keterangan',
        'filepdf',
        'publikasi',
        'status_fatwa',
        'views',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'publikasi' => 'boolean',
        'views' => 'integer',
    ];

    /**
     * Relasi ke Kategori Fatwa.
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriFatwa::class, 'kategori_fatwa_id');
    }

    /**
     * Alias relasi ke Kategori Fatwa (kategoriFatwa).
     */
    public function kategoriFatwa(): BelongsTo
    {
        return $this->kategori();
    }

    /**
     * URL publik untuk file PDF fatwa menggunakan asset().
     */
    public function getFileUrlAttribute(): ?string
    {
        return $this->filepdf ? asset('uploads/fatwa/'.basename($this->filepdf)) : null;
    }
}
