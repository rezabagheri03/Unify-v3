<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemConfig;

class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        SystemConfig::updateOrCreate(['key' => 'brand_name'], ['value' => 'Unify']);
        SystemConfig::updateOrCreate(['key' => 'logo_path'], ['value' => '/uploads/branding/logo.png']);
        SystemConfig::updateOrCreate(['key' => 'storage_limit_gb'], ['value' => '50']);

        $this->command->info('System config seeded (brand_name, logo_path, storage_limit_gb).');
    }
}
