<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('team_players', function (Blueprint $table) {
            // Xóa foreign key cũ
            $table->dropForeign(['member_id']);
            
            // Đổi tên cột từ member_id thành user_id
            $table->renameColumn('member_id', 'user_id');
            
            // Thêm foreign key mới
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_players', function (Blueprint $table) {
            // Xóa foreign key mới
            $table->dropForeign(['user_id']);
            
            // Đổi tên cột từ user_id thành member_id
            $table->renameColumn('user_id', 'member_id');
            
            // Thêm foreign key cũ
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });
    }
};
