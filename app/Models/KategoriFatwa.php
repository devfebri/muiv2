<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriFatwa extends Model
{
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'nama',
        'slug',
        'deskripsi',
        'aktif',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'aktif' => 'boolean',
    ];

    /**
     * Relasi ke data Fatwa yang berada dalam kategori ini.
     */
    public function fatwas(): HasMany
    {
        return $this->hasMany(Fatwa::class, 'kategori_fatwa_id');
    }
}
