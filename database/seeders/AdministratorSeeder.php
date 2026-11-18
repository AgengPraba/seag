<?php

namespace Database\Seeders;

use App\Models\Administrator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdministratorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Administrator::updateOrCreate(
            ['email' => 'admin@rsud-tidar.local'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('AdminRSUDTidar2026!'),
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );

        Administrator::updateOrCreate(
            ['email' => 'operator@rsud-tidar.local'],
            [
                'name' => 'Access Operator',
                'password' => Hash::make('OperatorRSUD2026!'),
                'role' => 'access_admin',
                'status' => 'active',
            ]
        );

        Administrator::updateOrCreate(
            ['email' => 'auditor@rsud-tidar.local'],
            [
                'name' => 'Security Auditor',
                'password' => Hash::make('AuditorRSUD2026!'),
                'role' => 'security_auditor',
                'status' => 'active',
            ]
        );
    }
}
