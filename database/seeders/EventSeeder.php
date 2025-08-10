<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;
class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Event::create([
            'title' => 'Buổi tập bóng đá tuần 1',
            'description' => 'Buổi tập kỹ thuật và chiến thuật cơ bản',
            'start_date' => now()->addDays(1)->setTime(19, 0),
            'end_date' => now()->addDays(1)->setTime(21, 0),
            'location' => 'Sân bóng đá Cầu Giấy',
            'max_participants' => 20,
            'status' => 'upcoming'
        ]);

        Event::create([
            'title' => 'Giải đấu nội bộ tháng 1',
            'description' => 'Giải đấu bóng đá nội bộ giữa các đội',
            'start_date' => now()->addDays(7)->setTime(14, 0),
            'end_date' => now()->addDays(7)->setTime(18, 0),
            'location' => 'Sân thể thao Mỹ Đình',
            'max_participants' => 25,
            'status' => 'upcoming'
        ]);
    }
}
