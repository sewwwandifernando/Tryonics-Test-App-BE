<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'mobileNumber' => '1234567890',
            'address' => '123 Test St, Test City',
            'dateOfBirth' => '1990-01-01',
            'password' => Hash::make('12345678'),
        ]);

        $this->call(RolePermissionSeeder::class);
    }
}
