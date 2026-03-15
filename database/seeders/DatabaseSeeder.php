<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('JOYTEL_DEV_EMAIL', 'dev@joytel.com')],
            [
                'name' => 'JoyTel Developer',
                'password' => env('JOYTEL_DEV_PASSWORD', 'dev12345'),
            ]
        );

        User::updateOrCreate(
            ['email' => env('JOYTEL_ADMIN_EMAIL', 'admin@joytel.com')],
            [
                'name' => 'JoyTel Admin',
                'password' => env('JOYTEL_ADMIN_PASSWORD', 'admin12345'),
            ]
        );

        // Seed products
        $this->call([
            ProductSeeder::class,
        ]);
    }
}
