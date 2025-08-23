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
        Schema::table('user_clubs', function (Blueprint $table) {
            // Thêm trường status để quản lý trạng thái thành viên
            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'inactive'])
                  ->default('pending')
                  ->after('role');
            
            // Thêm trường approved_at để lưu thời gian admin duyệt
            $table->timestamp('approved_at')->nullable()->after('status');
            
            // Thêm trường approved_by để lưu admin đã duyệt
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            // Thêm trường rejection_reason để lưu lý do từ chối
            $table->text('rejection_reason')->nullable()->after('approved_by');
            
            // Thêm index cho status để tối ưu query
            $table->index(['club_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_clubs', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['club_id', 'status']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn([
                'status',
                'approved_at',
                'approved_by',
                'rejection_reason'
            ]);
        });
    }
};
