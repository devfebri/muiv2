<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    /**
     * Tampilkan halaman pengaturan admin.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'profil');
        $settings = Setting::getAllSettings();

        return view('pengaturan.index', compact('tab', 'settings'));
    }

    /**
     * Simpan pembaruan pengaturan.
     */
    public function update(Request $request): RedirectResponse
    {
        $section = $request->input('_section', 'profil');

        if ($section === 'profil') {
            $data = $request->validate([
                'profil_title' => 'required|string|max:255',
                'profil_subtitle' => 'nullable|string',
                'profil_sekilas_1' => 'nullable|string',
                'profil_sekilas_2' => 'nullable|string',
                'profil_sekilas_3' => 'nullable|string',
                'profil_peran_1_title' => 'nullable|string|max:255',
                'profil_peran_1_desc' => 'nullable|string',
                'profil_peran_2_title' => 'nullable|string|max:255',
                'profil_peran_2_desc' => 'nullable|string',
                'profil_peran_3_title' => 'nullable|string|max:255',
                'profil_peran_3_desc' => 'nullable|string',
                'profil_tugas_pokok' => 'nullable|string',
                'profil_tgl_berdiri' => 'nullable|string|max:255',
                'profil_sifat_lembaga' => 'nullable|string|max:255',
                'profil_alamat_kantor' => 'nullable|string',
            ]);

            foreach ($data as $key => $val) {
                Setting::set($key, $val, 'profil');
            }
        } elseif ($section === 'visi_misi') {
            $data = $request->validate([
                'visi_title' => 'required|string|max:255',
                'visi_subtitle' => 'nullable|string',
                'visi_text' => 'required|string',
                'misi_list' => 'nullable|string',
                'wasathiyah_title' => 'nullable|string|max:255',
                'wasathiyah_desc' => 'nullable|string',
            ]);

            foreach ($data as $key => $val) {
                Setting::set($key, $val, 'visi_misi');
            }
        } elseif ($section === 'struktur') {
            $data = $request->validate([
                'struktur_title' => 'required|string|max:255',
                'struktur_subtitle' => 'nullable|string',
                'struktur_dewan_pertimbangan_desc' => 'nullable|string',
                'struktur_ketua_pertimbangan' => 'nullable|string|max:255',
                'struktur_anggota_pertimbangan' => 'nullable|string|max:255',
                'struktur_pimpinan_harian_desc' => 'nullable|string',
                'struktur_ketua_umum' => 'nullable|string|max:255',
                'struktur_ketua_umum_desc' => 'nullable|string',
                'struktur_wakil_ketua_umum' => 'nullable|string|max:255',
                'struktur_wakil_ketua_umum_desc' => 'nullable|string',
                'struktur_sekjen' => 'nullable|string|max:255',
                'struktur_sekjen_desc' => 'nullable|string',
                'struktur_bendahara_umum' => 'nullable|string|max:255',
                'struktur_bendahara_umum_desc' => 'nullable|string',
                'struktur_ketua_bidang' => 'nullable|string|max:255',
                'struktur_ketua_bidang_desc' => 'nullable|string',
                'struktur_komisi_list' => 'nullable|string',
                'struktur_bagan_gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
                'hapus_bagan_gambar' => 'nullable|boolean',
            ]);

            if ($request->hasFile('struktur_bagan_gambar')) {
                $oldImage = Setting::get('struktur_bagan_gambar');
                if ($oldImage && File::exists(public_path('uploads/pengaturan/'.basename($oldImage)))) {
                    File::delete(public_path('uploads/pengaturan/'.basename($oldImage)));
                }

                $file = $request->file('struktur_bagan_gambar');
                $filename = 'bagan_'.time().'_'.Str::random(8).'.'.strtolower($file->getClientOriginalExtension());
                File::ensureDirectoryExists(public_path('uploads/pengaturan'));
                $file->move(public_path('uploads/pengaturan'), $filename);
                Setting::set('struktur_bagan_gambar', $filename, 'struktur');
            } elseif ($request->boolean('hapus_bagan_gambar')) {
                $oldImage = Setting::get('struktur_bagan_gambar');
                if ($oldImage && File::exists(public_path('uploads/pengaturan/'.basename($oldImage)))) {
                    File::delete(public_path('uploads/pengaturan/'.basename($oldImage)));
                }
                Setting::set('struktur_bagan_gambar', null, 'struktur');
            }

            unset($data['struktur_bagan_gambar'], $data['hapus_bagan_gambar']);

            foreach ($data as $key => $val) {
                Setting::set($key, $val, 'struktur');
            }
        } elseif ($section === 'kontak') {
            $data = $request->validate([
                'kontak_title' => 'required|string|max:255',
                'kontak_subtitle' => 'nullable|string',
                'kontak_alamat' => 'nullable|string',
                'kontak_telepon' => 'nullable|string|max:255',
                'kontak_whatsapp' => 'nullable|string|max:255',
                'kontak_email' => 'nullable|email|max:255',
                'kontak_jam_layanan' => 'nullable|string|max:255',
                'kontak_maps_embed' => 'nullable|string',
                'kontak_instagram' => 'nullable|string|max:255',
                'kontak_youtube' => 'nullable|string|max:255',
                'kontak_facebook' => 'nullable|string|max:255',
                'kontak_twitter' => 'nullable|string|max:255',
                'kontak_tiktok' => 'nullable|string|max:255',
            ]);

            foreach ($data as $key => $val) {
                Setting::set($key, $val, 'kontak');
            }
        } elseif ($section === 'livechat') {
            $data = $request->validate([
                'chat_is_enabled' => 'nullable|string',
                'chat_operational_start' => 'required|string|max:10',
                'chat_operational_end' => 'required|string|max:10',
                'chat_operational_days' => 'nullable|string|max:50',
                'chat_avg_wait_minutes' => 'required|integer|min:1',
                'chat_bot_greeting' => 'required|string',
                'chat_offline_message' => 'required|string',
            ]);

            $data['chat_is_enabled'] = $request->has('chat_is_enabled') ? '1' : '0';

            foreach ($data as $key => $val) {
                Setting::set($key, (string) $val, 'livechat');
            }
        }

        Setting::clearCache();

        $sectionLabels = [
            'profil' => 'Profil MUI',
            'visi_misi' => 'Visi & Misi',
            'struktur' => 'Struktur Organisasi',
            'kontak' => 'Kontak & Media Sosial',
            'livechat' => 'Live Chat & Jam Kerja',
        ];

        $label = $sectionLabels[$section] ?? 'Pengaturan';

        return redirect()->route('admin.pengaturan.index', ['tab' => $section])
            ->with('pesan', 'Pengaturan '.$label.' berhasil disimpan!');
    }
}
