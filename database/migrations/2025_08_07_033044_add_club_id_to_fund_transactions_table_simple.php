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
        // Cập nhật dữ liệu hiện có
        $defaultClub = DB::table('clubs')->first();
        if ($defaultClub) {
            DB::table('fund_transactions')->whereNull('club_id')->update(['club_id' => $defaultClub->id]);
        }

        // Thêm index
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->index('club_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->dropIndex(['club_id']);
        });
    }
};
