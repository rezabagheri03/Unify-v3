<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ShamsiService;

class ShamsiDateTest extends TestCase
{
    public function test_shamsi_service_converts_dates()
    {
        $shamsi = ShamsiService::toShamsi('2025-01-20');
        $this->assertNotEmpty($shamsi);
    }
}