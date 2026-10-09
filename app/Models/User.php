<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'name_gelar',
    'jk',
    'alamat',
    'nohp',
    'username',
    'role',
    'menu_permissions',
    'email',
    'foto',
    'password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Daftar menu operasional yang dapat dibagikan oleh admin ke operator.
     *
     * @var array<string, array{label: string, group: string, icon: string, description: string, route: string}>
     */
    public const OPERATOR_PERMISSIONS = [
        'berita' => [
            'label' => 'Berita & Artikel',
            'group' => 'Konten',
            'icon' => 'mdi mdi-newspaper',
            'description' => 'Membuat, mengedit, dan mengelola berita & artikel website.',
            'route' => 'operator.berita.index',
        ],
        'kategori' => [
            'label' => 'Kategori Berita',
            'group' => 'Konten',
            'icon' => 'mdi mdi-tag-multiple',
            'description' => 'Mengatur kategori artikel dan berita.',
            'route' => 'operator.kategori.index',
        ],
        'surat' => [
            'label' => 'Arsip Surat',
            'group' => 'Arsip',
            'icon' => 'mdi mdi-email-outline',
            'description' => 'Mengelola arsip surat masuk dan keluar.',
            'route' => 'operator.surat.index',
        ],
        'fatwa' => [
            'label' => 'Fatwa MUI',
            'group' => 'Arsip',
            'icon' => 'mdi mdi-book-open-variant',
            'description' => 'Mengelola dokumen, naskah, dan arsip fatwa MUI.',
            'route' => 'operator.fatwa.index',
        ],
        'kategori-fatwa' => [
            'label' => 'Kategori Fatwa',
            'group' => 'Arsip',
            'icon' => 'mdi mdi-label-outline',
            'description' => 'Mengelola kategori dan klasifikasi bidang fatwa.',
            'route' => 'operator.kategori-fatwa.index',
        ],
        'livechat' => [
            'label' => 'Live Chat Realtime',
            'group' => 'Layanan',
            'icon' => 'mdi mdi-chat-processing-outline',
            'description' => 'Menerima dan membalas obrolan langsung masyarakat secara realtime.',
            'route' => 'admin.livechat.index',
        ],
        'konsultasi' => [
            'label' => 'Konsultasi / Tanya Ulama',
            'group' => 'Layanan',
            'icon' => 'mdi mdi-forum',
            'description' => 'Membaca dan menjawab pertanyaan konsultasi syariah dari jamaah.',
            'route' => 'operator.konsultasi.index',
        ],
    ];

    /**
     * Default hak akses menu untuk operator baru:
     * Berita & Artikel, serta semua menu di bidang Layanan (Live Chat & Konsultasi).
     *
     * @var array<string>
     */
    public const DEFAULT_OPERATOR_PERMISSIONS = [
        'berita',
        'livechat',
        'konsultasi',
    ];

    /**
     * Get the profile photo URL or null.
     */
    public function getFotoUrlAttribute(): ?string
    {
        if ($this->foto && file_exists(public_path('uploads/profil/'.$this->foto))) {
            return asset('uploads/profil/'.$this->foto);
        }

        return null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'menu_permissions' => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    /**
     * Periksa apakah user memiliki hak akses ke menu tertentu.
     */
    public function hasMenuPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isOperator()) {
            return false;
        }

        // Jika null (operator baru), default hanya Berita & Artikel dan semua bidang Layanan
        if (is_null($this->menu_permissions)) {
            return in_array($permission, self::DEFAULT_OPERATOR_PERMISSIONS, true);
        }

        return in_array($permission, $this->menu_permissions, true);
    }

    /**
     * Ambil array kunci permission yang dimiliki user.
     *
     * @return array<string>
     */
    public function getAssignedPermissions(): array
    {
        if ($this->isAdmin()) {
            return array_keys(self::OPERATOR_PERMISSIONS);
        }

        if (is_null($this->menu_permissions)) {
            return self::DEFAULT_OPERATOR_PERMISSIONS;
        }

        return (array) $this->menu_permissions;
    }
}
