<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ShamsiService;

class ShamsiServiceTest extends TestCase
{
    public function test_converts_gregorian_to_shamsi()
    {
        $shamsi = ShamsiService::toShamsi('2025-01-20');
        $this->assertEquals('1403/10/30', $shamsi);
    }

    public function test_converts_shamsi_to_gregorian()
    {
        $gregorian = ShamsiService::toGregorian('1403/05/15');
        $this->assertStringContainsString('2024-08-05', $gregorian);
    }
}