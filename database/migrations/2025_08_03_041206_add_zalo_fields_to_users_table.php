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
            $table->string('role')->nullable();
            $table->string('avatar')->nullable();
            $table->date('join_date')->nullable();
            $table->string('zalo_gid')->nullable()->unique()->after('email');
            $table->string('zalo_name')->nullable()->after('zalo_gid');
            $table->string('zalo_avatar')->nullable()->after('zalo_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar','role','join_date','zalo_gid', 'zalo_name', 'zalo_avatar']);
        });
    }
};
