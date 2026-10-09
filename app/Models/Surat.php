<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Surat extends Model
{
    /** @var array<string> */
    protected $fillable = [
        'user_id',
        'nomor_surat',
        'perihal',
        'tanggal_surat',
        'file_surat',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tanggal_surat' => 'date',
    ];

    /** Pengunggah surat. */
    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * URL publik untuk file surat menggunakan asset().
     */
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_surat ? asset('uploads/surat/'.basename($this->file_surat)) : null;
    }
}
