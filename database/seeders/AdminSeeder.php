<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'name' => 'Admin Vanilla Royal',
            'email' => 'admin@vanillaroyal.com',
            'password' => Hash::make('admin123'),
        ]);
    }
}
