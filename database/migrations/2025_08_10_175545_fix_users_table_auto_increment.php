<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kiểm tra xem có phải MySQL không
        if (DB::connection()->getDriverName() === 'mysql') {
            // Sửa lỗi auto-increment cho bảng users
            DB::statement('ALTER TABLE users MODIFY id BIGINT UNSIGNED AUTO_INCREMENT');
            
            // Đảm bảo auto-increment hoạt động
            DB::statement('ALTER TABLE users AUTO_INCREMENT = 1');
            
            // Kiểm tra và sửa sequence nếu cần
            $maxId = DB::table('users')->max('id');
            if ($maxId) {
                DB::statement("ALTER TABLE users AUTO_INCREMENT = " . ($maxId + 1));
            }
        }
        
        // Nếu là SQLite, không cần làm gì vì SQLite tự động xử lý auto-increment
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không cần rollback cho migration này
    }
};
