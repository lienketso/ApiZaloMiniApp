<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Club;

class ClubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tìm user đầu tiên hoặc tạo user mới
        $user = \App\Models\User::first();
        
        if (!$user) {
            $user = \App\Models\User::factory()->create();
        }

        Club::create([
            'name' => 'My Club',
            'sport' => 'Bóng đá',
            'address' => 'Hà Nội, Việt Nam',
            'phone' => '0123456789',
            'email' => 'myclub@example.com',
            'description' => 'Câu lạc bộ bóng đá cộng đồng với mục tiêu phát triển tài năng và tạo môi trường thi đấu lành mạnh',
            'is_setup' => true,
            'created_by' => $user->id,
        ]);

        // Tạo relationship user_club
        $club = Club::where('name', 'My Club')->first();
        if ($club) {
            $club->users()->attach($user->id, [
                'role' => 'admin',
                'joined_date' => now(),
                'notes' => 'Club creator',
                'is_active' => true
            ]);
        }
    }
}
