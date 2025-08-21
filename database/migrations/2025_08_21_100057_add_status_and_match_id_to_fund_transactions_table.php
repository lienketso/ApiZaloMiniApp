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
        Schema::table('fund_transactions', function (Blueprint $table) {
            // Thêm trường status để theo dõi trạng thái giao dịch
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending')->after('notes');
            
            // Thêm trường match_id để liên kết với trận đấu
            $table->unsignedBigInteger('match_id')->nullable()->after('status');
            
            // Thêm trường user_id để liên kết với người dùng cụ thể
            $table->unsignedBigInteger('user_id')->nullable()->after('match_id');
            
            // Thêm foreign key constraints
            $table->foreign('match_id')->references('id')->on('matches')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Thêm indexes
            $table->index(['status', 'match_id']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['match_id']);
            $table->dropForeign(['user_id']);
            
            // Drop indexes
            $table->dropIndex(['status', 'match_id']);
            $table->dropIndex(['user_id', 'status']);
            
            // Drop columns
            $table->dropColumn(['status', 'match_id', 'user_id']);
        });
    }
};
