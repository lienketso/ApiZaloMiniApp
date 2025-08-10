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
            DB::table('members')->whereNull('club_id')->update(['club_id' => $defaultClub->id]);
        }

        // Thêm foreign key
        Schema::table('members', function (Blueprint $table) {
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->index('club_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->dropIndex(['club_id']);
        });
    }
};
