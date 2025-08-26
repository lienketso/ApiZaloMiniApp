<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'description' => 'Gói cơ bản cho câu lạc bộ nhỏ',
                'price' => 199000,
                'billing_cycle' => 'monthly',
                'duration_days' => 30,
                'features' => [
                    'Quản lý thành viên cơ bản',
                    'Tạo sự kiện',
                    'Quản lý tài chính cơ bản',
                    'Hỗ trợ email'
                ],
                'is_active' => true
            ],
            [
                'name' => 'Premium',
                'description' => 'Gói nâng cao cho câu lạc bộ vừa và lớn',
                'price' => 399000,
                'billing_cycle' => 'monthly',
                'duration_days' => 30,
                'features' => [
                    'Tất cả tính năng Basic',
                    'Quản lý thành viên nâng cao',
                    'Thống kê chi tiết',
                    'Tích hợp Zalo',
                    'Hỗ trợ ưu tiên'
                ],
                'is_active' => true
            ],
            [
                'name' => 'Enterprise',
                'description' => 'Gói doanh nghiệp cho câu lạc bộ lớn',
                'price' => 799000,
                'billing_cycle' => 'monthly',
                'duration_days' => 30,
                'features' => [
                    'Tất cả tính năng Premium',
                    'API tùy chỉnh',
                    'Báo cáo nâng cao',
                    'Hỗ trợ 24/7',
                    'Tùy chỉnh giao diện'
                ],
                'is_active' => true
            ],
            [
                'name' => 'Yearly Basic',
                'description' => 'Gói cơ bản trả theo năm (tiết kiệm 20%)',
                'price' => 1910000,
                'billing_cycle' => 'yearly',
                'duration_days' => 365,
                'features' => [
                    'Quản lý thành viên cơ bản',
                    'Tạo sự kiện',
                    'Quản lý tài chính cơ bản',
                    'Hỗ trợ email',
                    'Tiết kiệm 20% so với trả tháng'
                ],
                'is_active' => true
            ],
            [
                'name' => 'Yearly Premium',
                'description' => 'Gói nâng cao trả theo năm (tiết kiệm 20%)',
                'price' => 3830000,
                'billing_cycle' => 'yearly',
                'duration_days' => 365,
                'features' => [
                    'Tất cả tính năng Premium',
                    'Tiết kiệm 20% so với trả tháng'
                ],
                'is_active' => true
            ]
        ];

        foreach ($plans as $planData) {
            Plan::create($planData);
        }
    }
}
