<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    use HasFactory;

    protected $table = 'chat_sessions';

    protected $fillable = [
        'session_token',
        'nama_pengunjung',
        'email_pengunjung',
        'nohp_pengunjung',
        'topik',
        'status',
        'antrian_nomor',
        'operator_id',
        'started_at',
        'closed_at',
        'last_activity_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'antrian_nomor' => 'integer',
    ];

    /**
     * Relasi ke petugas/operator yang melayani.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Relasi ke pesan-pesan dalam sesi ini.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_session_id')->orderBy('created_at', 'asc');
    }

    /**
     * Hitung urutan antrian aktif saat ini.
     */
    public function getAntrianPositionAttribute(): int
    {
        if ($this->status !== 'menunggu') {
            return 0;
        }

        return (int) self::query()
            ->where('status', 'menunggu')
            ->where('id', '<=', $this->id)
            ->count();
    }

    /**
     * Estimasi waktu tunggu (dalam menit).
     */
    public function getEstimasiTungguMenitAttribute(): int
    {
        $pos = $this->antrian_position;
        if ($pos <= 1) {
            return 2; // Segera dilayani
        }

        $ratePerQueue = (int) Setting::get('chat_avg_wait_minutes', 4);

        return ($pos - 1) * $ratePerQueue;
    }

    /**
     * Scope sesi aktif atau menunggu.
     */
    public function scopeActiveOrWaiting(Builder $query): Builder
    {
        return $query->whereIn('status', ['menunggu', 'aktif']);
    }
}
