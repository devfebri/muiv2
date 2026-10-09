<?php

use App\Http\Controllers\AdminLiveChatController;
use App\Http\Controllers\BeritaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FatwaController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\KategoriFatwaController;
use App\Http\Controllers\KonsultasiAdminController;
use App\Http\Controllers\KonsultasiController;
use App\Http\Controllers\LiveChatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OperatorPermissionController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WelcomeController;
use App\Models\Fatwa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [WelcomeController::class, 'index'])->name('home.public');

Route::get('/berita', [BeritaController::class, 'list'])->name('berita.list');
Route::get('/berita/{slug}', [BeritaController::class, 'detail'])->name('berita.detail');

Route::get('/profil-mui', [PageController::class, 'profil'])->name('profilemui');
Route::get('/visi-misi', [PageController::class, 'visiMisi'])->name('visi-misi');
Route::get('/struktur-organisasi', [PageController::class, 'strukturOrganisasi'])->name('struktur-organisasi');
Route::get('/kontak', [PageController::class, 'kontak'])->name('kontak');

Route::get('/tanya-ulama', [KonsultasiController::class, 'index'])->name('tanya-ulama');
Route::post('/tanya-ulama', [KonsultasiController::class, 'store'])->name('tanya-ulama.store');
Route::get('/konsultasi', [KonsultasiController::class, 'list'])->name('konsultasi.list');
Route::get('/konsultasi/{konsultasi}', [KonsultasiController::class, 'detail'])->name('konsultasi.detail');

Route::get('/fatwa', [FatwaController::class, 'publicList'])->name('fatwa');
Route::get('/fatwa/{fatwa}', [FatwaController::class, 'publicDetail'])->whereNumber('fatwa')->name('fatwa.detail');
Route::post('/fatwa/{fatwa}/baca', [FatwaController::class, 'incrementViews'])->name('fatwa.increment-views');
Route::get('/surat', [SuratController::class, 'publicList'])->name('surat');
Route::get('/cari', SearchController::class)->middleware('throttle:60,1')->name('search');

Auth::routes(['register' => false]);

/*
|--------------------------------------------------------------------------
| Post-Login Redirect
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    return match (auth()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'operator' => redirect()->route('operator.dashboard'),
        default => redirect()->route('login'),
    };
})->middleware('auth')->name('dashboard');

/*
|--------------------------------------------------------------------------
| User Profile Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profil-akun', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profil-akun', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Users (AJAX CRUD)
    Route::resource('users', UserController::class)->only([
        'index',
        'store',
        'update',
        'destroy',
    ]);

    // Berita (Full-page create/edit, AJAX delete, AJAX index)
    Route::resource('berita', BeritaController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['berita' => 'berita']);

    Route::resource('kategori', KategoriController::class)->only([
        'index',
        'store',
        'update',
        'destroy',
    ]);

    Route::resource('surat', SuratController::class)->only([
        'index',
        'store',
        'update',
        'destroy',
    ]);

    // Fatwa (AJAX CRUD + PDF upload)
    Route::resource('fatwa', FatwaController::class)->only([
        'index',
        'store',
        'update',
        'destroy',
    ]);
    Route::patch('fatwa/{fatwa}/toggle-publikasi', [FatwaController::class, 'togglePublikasi'])
        ->name('fatwa.togglePublikasi');

    // Kategori Fatwa (AJAX CRUD)
    Route::resource('kategori-fatwa', KategoriFatwaController::class)->only([
        'index',
        'store',
        'update',
        'destroy',
    ]);
    Route::patch('kategori-fatwa/{kategori_fatwa}/toggle-status', [KategoriFatwaController::class, 'toggleStatus'])
        ->name('kategori-fatwa.toggleStatus');

    // Konsultasi (View Only)
    Route::get('konsultasi', [KonsultasiAdminController::class, 'index'])->name('konsultasi.index');
    Route::get('konsultasi/{konsultasi}', [KonsultasiAdminController::class, 'show'])->name('konsultasi.show');

    // Pengaturan (Tentang Kami)
    Route::get('pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::post('pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');

    // Hak Akses & Pembagian Tugas Operator
    Route::prefix('operator-permissions')->name('operator-permissions.')->group(function () {
        Route::get('/', [OperatorPermissionController::class, 'index'])->name('index');
        Route::get('/{user}/edit', [OperatorPermissionController::class, 'edit'])->name('edit');
        Route::put('/{user}', [OperatorPermissionController::class, 'update'])->name('update');
        Route::post('/{user}/grant-all', [OperatorPermissionController::class, 'grantAll'])->name('grant-all');
        Route::post('/{user}/revoke-all', [OperatorPermissionController::class, 'revokeAll'])->name('revoke-all');
    });
});

/*
|--------------------------------------------------------------------------
| Operator Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:operator'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Berita & Artikel
    Route::middleware('operator.permission:berita')->group(function () {
        Route::resource('berita', BeritaController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['berita' => 'berita']);
    });

    // Kategori Berita
    Route::middleware('operator.permission:kategori')->group(function () {
        Route::resource('kategori', KategoriController::class)->only([
            'index',
            'store',
            'update',
            'destroy',
        ]);
    });

    // Arsip Surat
    Route::middleware('operator.permission:surat')->group(function () {
        Route::resource('surat', SuratController::class)->only([
            'index',
            'store',
            'update',
            'destroy',
        ]);
    });

    // Fatwa MUI
    Route::middleware('operator.permission:fatwa')->group(function () {
        Route::resource('fatwa', FatwaController::class)->only([
            'index',
            'store',
            'update',
            'destroy',
        ]);
        Route::patch('fatwa/{fatwa}/toggle-publikasi', [FatwaController::class, 'togglePublikasi'])
            ->name('fatwa.togglePublikasi');
    });

    // Kategori Fatwa
    Route::middleware('operator.permission:kategori-fatwa')->group(function () {
        Route::resource('kategori-fatwa', KategoriFatwaController::class)->only([
            'index',
            'store',
            'update',
            'destroy',
        ]);
        Route::patch('kategori-fatwa/{kategori_fatwa}/toggle-status', [KategoriFatwaController::class, 'toggleStatus'])
            ->name('kategori-fatwa.toggleStatus');
    });

    // Konsultasi (View + Reply)
    Route::middleware('operator.permission:konsultasi')->group(function () {
        Route::get('konsultasi', [KonsultasiAdminController::class, 'index'])->name('konsultasi.index');
        Route::get('konsultasi/{konsultasi}', [KonsultasiAdminController::class, 'show'])->name('konsultasi.show');
        Route::post('konsultasi/{konsultasi}/jawab', [KonsultasiAdminController::class, 'jawab'])->name('konsultasi.jawab');
        Route::delete('konsultasi/{konsultasi}', [KonsultasiAdminController::class, 'destroy'])->name('konsultasi.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Live Chat Frontend (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('livechat')->name('livechat.')->group(function () {
    Route::get('/init', [LiveChatController::class, 'init'])->name('init');
    Route::post('/start', [LiveChatController::class, 'start'])->name('start');
    Route::post('/send', [LiveChatController::class, 'send'])->name('send');
    Route::post('/ask-faq', [LiveChatController::class, 'askFaq'])->name('ask-faq');
    Route::get('/poll', [LiveChatController::class, 'poll'])->name('poll');
    Route::get('/stream', [LiveChatController::class, 'stream'])->name('stream');
    Route::post('/close', [LiveChatController::class, 'close'])->name('close');
});

/*
|--------------------------------------------------------------------------
| Live Chat Panel Petugas (Admin & Operator)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin,operator', 'operator.permission:livechat'])->prefix('petugas/livechat')->name('admin.livechat.')->group(function () {
    Route::get('/', [AdminLiveChatController::class, 'index'])->name('index');
    Route::post('/take/{session}', [AdminLiveChatController::class, 'take'])->name('take');
    Route::get('/room/{session}', [AdminLiveChatController::class, 'show'])->name('show');
    Route::post('/room/{session}/send', [AdminLiveChatController::class, 'sendMessage'])->name('send');
    Route::get('/room/{session}/poll', [AdminLiveChatController::class, 'pollSession'])->name('poll-session');
    Route::post('/room/{session}/close', [AdminLiveChatController::class, 'close'])->name('close');
    Route::get('/poll-overview', [AdminLiveChatController::class, 'pollOverview'])->name('poll-overview');
    Route::get('/export-csv', [AdminLiveChatController::class, 'exportCsv'])->name('export-csv');
    Route::get('/transcript/{session}', [AdminLiveChatController::class, 'transcript'])->name('transcript');
    Route::post('/faq', [AdminLiveChatController::class, 'storeFaq'])->name('faq.store');
    Route::put('/faq/{faq}', [AdminLiveChatController::class, 'updateFaq'])->name('faq.update');
    Route::delete('/faq/{faq}', [AdminLiveChatController::class, 'deleteFaq'])->name('faq.destroy');
});
