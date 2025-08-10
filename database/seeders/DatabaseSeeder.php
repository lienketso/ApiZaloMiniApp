<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use App\Models\FundTransaction;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

//        User::factory()->create([
//            'name' => 'Test User',
//            'email' => 'test@example.com',
//        ]);
        $user = User::first();

        if (!$user) {
            $user = User::factory()->create();
        }

        $transactions = [
            [
                'type' => 'income',
                'amount' => 500000,
                'description' => 'Thu phí tháng 12',
                'category' => 'Phí thành viên',
                'transaction_date' => '2024-12-15',
                'created_by' => $user->id
            ],
            [
                'type' => 'expense',
                'amount' => 300000,
                'description' => 'Mua bóng đá',
                'category' => 'Thiết bị',
                'transaction_date' => '2024-12-10',
                'created_by' => $user->id
            ],
            [
                'type' => 'income',
                'amount' => 200000,
                'description' => 'Thu phí tháng 11',
                'category' => 'Phí thành viên',
                'transaction_date' => '2024-11-30',
                'created_by' => $user->id
            ],
            [
                'type' => 'expense',
                'amount' => 150000,
                'description' => 'Thuê sân bóng',
                'category' => 'Sân bãi',
                'transaction_date' => '2024-11-25',
                'created_by' => $user->id
            ]
        ];

        foreach ($transactions as $transaction) {
            FundTransaction::create($transaction);
        }
    }
}
