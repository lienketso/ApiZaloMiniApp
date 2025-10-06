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
            // Index cho status để tối ưu queries
            $table->index(['user_id', 'status']);
            $table->index(['club_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::table('clubs', function (Blueprint $table) {
            // Index cho is_setup để tối ưu available clubs query
            $table->index(['is_setup', 'created_at']);
            $table->index('created_by');
        });

        Schema::table('matches', function (Blueprint $table) {
            // Composite index cho club queries
            $table->index(['club_id', 'status', 'match_date']);
            $table->index(['status', 'match_date']);
        });

        Schema::table('events', function (Blueprint $table) {
            // Index cho events queries
            $table->index(['club_id', 'start_date']);
        });

        Schema::table('fund_transactions', function (Blueprint $table) {
            // Index cho fund queries
            $table->index(['club_id', 'transaction_date']);
            $table->index(['type', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_clubs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['club_id', 'status']);
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('clubs', function (Blueprint $table) {
            $table->dropIndex(['is_setup', 'created_at']);
            $table->dropIndex(['created_by']);
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex(['club_id', 'status', 'match_date']);
            $table->dropIndex(['status', 'match_date']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['club_id', 'start_date']);
        });

        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->dropIndex(['club_id', 'transaction_date']);
            $table->dropIndex(['type', 'transaction_date']);
        });
    }
};
