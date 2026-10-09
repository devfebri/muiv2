<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * Get a setting value by key with optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::getAllSettings();

        return $all[$key] ?? $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget('site_settings_all');

        return $setting;
    }

    /**
     * Get all settings from cache.
     *
     * @return array<string, mixed>
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('site_settings_all', 3600, function () {
            return self::query()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Clear settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget('site_settings_all');
    }

    /**
     * Identitas situs (nama, kontak, media sosial) yang dibagikan ke seluruh tampilan.
     *
     * @return array<string, string|null>
     */
    public static function siteProfile(): array
    {
        try {
            $settings = self::getAllSettings();
        } catch (\Throwable) {
            // Basis data tidak tersedia (mis. saat merender halaman galat): gunakan nilai bawaan.
            $settings = [];
        }
        $value = fn (string $key, ?string $default = null): ?string => filled($settings[$key] ?? null) ? trim((string) $settings[$key]) : $default;

        return [
            'site_name' => $value('site_name', 'Majelis Ulama Indonesia'),
            'site_region' => $value('site_region', 'Kabupaten Batanghari'),
            'site_short' => $value('site_short', 'MUI Batanghari'),
            'site_description' => $value('site_description', 'Website resmi Majelis Ulama Indonesia (MUI) Kabupaten Batanghari, Provinsi Jambi. Informasi fatwa, berita, arsip surat, dan konsultasi keagamaan untuk umat.'),
            'logo_url' => asset('gambar/mui.png'),
            'address' => $value('kontak_alamat', $value('profil_alamat_kantor', 'Muara Bulian, Kabupaten Batanghari, Jambi')),
            'phone' => $value('kontak_telepon'),
            'whatsapp' => $value('kontak_whatsapp'),
            'email' => $value('kontak_email'),
            'office_hours' => $value('kontak_jam_layanan', 'Senin – Jumat: 08.00 – 16.00 WIB'),
            'maps_embed' => $value('kontak_maps_embed'),
            'facebook' => self::socialUrl($value('kontak_facebook'), 'https://www.facebook.com/', 'https://www.facebook.com/search/top?q='),
            'instagram' => self::socialUrl($value('kontak_instagram'), 'https://www.instagram.com/'),
            'youtube' => self::socialUrl($value('kontak_youtube'), 'https://www.youtube.com/@', 'https://www.youtube.com/results?search_query='),
            'tiktok' => self::socialUrl($value('kontak_tiktok'), 'https://www.tiktok.com/@'),
            'x-twitter' => self::socialUrl($value('kontak_twitter'), 'https://x.com/'),
        ];
    }

    /**
     * Ubah nilai pengaturan media sosial (URL, @handle, atau nama halaman) menjadi tautan.
     */
    private static function socialUrl(?string $value, string $profileBase, ?string $searchBase = null): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        if (str_contains($value, ' ')) {
            return $searchBase ? $searchBase.rawurlencode($value) : null;
        }

        return $profileBase.ltrim($value, '@');
    }
}
