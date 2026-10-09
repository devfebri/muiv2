<?php

namespace Tests\Unit;

use App\Services\LiveChatService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OperationalDaysTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function dayProvider(): array
    {
        return [
            'hari kerja' => ['1,2,3,4,5', 'Senin – Jumat'],
            'sampai sabtu' => ['1,2,3,4,5,6', 'Senin – Sabtu'],
            'setiap hari' => ['1,2,3,4,5,6,7', 'Setiap hari'],
            'tidak berurutan' => ['5,1,3', 'Senin, Rabu, Jumat'],
            'dua hari' => ['6,7', 'Sabtu, Minggu'],
            'kosong' => ['', 'Tidak ada hari layanan'],
        ];
    }

    #[DataProvider('dayProvider')]
    public function test_operational_days_are_formatted_as_readable_text(string $days, string $expected): void
    {
        $this->assertSame($expected, LiveChatService::formatOperationalDays($days));
    }
}
