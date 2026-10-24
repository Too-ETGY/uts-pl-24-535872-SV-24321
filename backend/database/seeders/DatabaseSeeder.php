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
        $user = User::firstOrCreate(
            ['email' => 'critic@example.com'],
            [
                'name' => 'Citizen Critic',
                'password' => bcrypt('password'),
            ]
        );

        if ($user->notes()->count() === 0) {
            $user->notes()->create([
                'title' => 'Formulir Rangkap Tiga',
                'content' => 'Birokrasi meminta fotokopi KTP yang sudah berstatus elektronik.',
            ]);
        }
    }
}
