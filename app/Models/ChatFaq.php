<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatFaq extends Model
{
    use HasFactory;

    protected $table = 'chat_faqs';

    protected $fillable = [
        'pertanyaan',
        'jawaban',
        'kategori',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    /**
     * Scope faq yang aktif saja.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('urutan', 'asc')->orderBy('id', 'asc');
    }
}
