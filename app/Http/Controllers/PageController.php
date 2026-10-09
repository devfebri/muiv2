<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Tampilkan halaman Profil MUI dengan data dinamis.
     */
    public function profil(): View
    {
        $settings = Setting::getAllSettings();

        return view('pages.profilemui', compact('settings'));
    }

    /**
     * Tampilkan halaman Visi & Misi dengan data dinamis.
     */
    public function visiMisi(): View
    {
        $settings = Setting::getAllSettings();

        return view('pages.visi-misi', compact('settings'));
    }

    /**
     * Tampilkan halaman Struktur Organisasi dengan data dinamis.
     */
    public function strukturOrganisasi(): View
    {
        $settings = Setting::getAllSettings();

        return view('pages.struktur-organisasi', compact('settings'));
    }

    /**
     * Tampilkan halaman Kontak dengan data dinamis.
     */
    public function kontak(): View
    {
        $settings = Setting::getAllSettings();

        return view('pages.kontak', compact('settings'));
    }
}
