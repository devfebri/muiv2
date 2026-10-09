<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $table = 'chat_messages';

    protected $fillable = [
        'chat_session_id',
        'sender_type',
        'sender_id',
        'sender_name',
        'pesan',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Relasi ke sesi chat.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    /**
     * Relasi ke model User (jika pengirim adalah operator/admin).
     */
    public function operatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
