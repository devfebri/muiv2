<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Konsultasi extends Model
{
    /** @var array<string> */
    protected $fillable = [
        'nama',
        'email',
        'usia',
        'jenis_kelamin',
        'kab_kota',
        'kategori',
        'pertanyaan',
        'status',
        'jawaban',
        'penjawab_id',
        'answered_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'usia' => 'integer',
        'answered_at' => 'datetime',
    ];

    public function penjawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penjawab_id');
    }

    /**
     * Nama penanya yang disamarkan untuk tampilan publik (mis. "Ahmad R.").
     */
    public function getNamaSamaranAttribute(): string
    {
        $parts = preg_split('/\s+/u', trim((string) $this->nama), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return 'Hamba Allah';
        }

        $initial = isset($parts[1]) ? ' '.mb_strtoupper(mb_substr($parts[1], 0, 1)).'.' : '';

        return $parts[0].$initial;
    }
}
