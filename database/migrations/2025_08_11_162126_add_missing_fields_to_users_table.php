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
        Schema::table('users', function (Blueprint $table) {
            // Chỉ thêm các trường còn thiếu
            if (!Schema::hasColumn('users', 'zalo_id')) {
                $table->string('zalo_id')->nullable()->after('avatar');
            }
            if (!Schema::hasColumn('users', 'zalo_access_token')) {
                $table->string('zalo_access_token')->nullable()->after('zalo_id');
            }
            if (!Schema::hasColumn('users', 'zalo_refresh_token')) {
                $table->string('zalo_refresh_token')->nullable()->after('zalo_access_token');
            }
            if (!Schema::hasColumn('users', 'zalo_token_expires_at')) {
                $table->timestamp('zalo_token_expires_at')->nullable()->after('zalo_refresh_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'zalo_id',
                'zalo_access_token',
                'zalo_refresh_token',
                'zalo_token_expires_at'
            ]);
        });
    }
};
