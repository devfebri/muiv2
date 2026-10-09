<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Menggunakan updateOrCreate agar idempotent — aman dijalankan berkali-kali.
     */
    public function run(): void
    {
        // 1. Admin User
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Operator User
        User::updateOrCreate(
            ['email' => 'operator@operator.com'],
            [
                'name' => 'Operator',
                'username' => 'operator',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'email_verified_at' => now(),
            ]
        );
        User::updateOrCreate(
            ['email' => 'operator3@operator.com'],
            [
                'name' => 'Operator3',
                'username' => 'operator3',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'email_verified_at' => now(),
            ]
        );
        User::updateOrCreate(
            ['email' => 'operator2@operator.com'],
            [
                'name' => 'Operator2',
                'username' => 'operator2',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'email_verified_at' => now(),
            ]
        );
        User::updateOrCreate(
            ['email' => 'operator1@operator.com'],
            [
                'name' => 'Operator1',
                'username' => 'operator1',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'email_verified_at' => now(),
            ]
        );

        // 3. Panggil seluruh seeder sampel data
        $this->call([
            KategoriSeeder::class,
            KategoriFatwaSeeder::class,
            BeritaSeeder::class,
            FatwaSeeder::class,
            SuratSeeder::class,
            KonsultasiSeeder::class,
        ]);
    }
}
