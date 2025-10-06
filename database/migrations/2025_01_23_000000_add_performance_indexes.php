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
            // Index cho status để tối ưu queries (chỉ thêm nếu chưa tồn tại)
            if (!Schema::hasIndex('user_clubs', 'user_clubs_user_id_status_index')) {
                $table->index(['user_id', 'status']);
            }
            if (!Schema::hasIndex('user_clubs', 'user_clubs_club_id_status_index')) {
                $table->index(['club_id', 'status']);
            }
            if (!Schema::hasIndex('user_clubs', 'user_clubs_status_created_at_index')) {
                $table->index(['status', 'created_at']);
            }
        });

        Schema::table('clubs', function (Blueprint $table) {
            // Index cho is_setup để tối ưu available clubs query
            if (!Schema::hasIndex('clubs', 'clubs_is_setup_created_at_index')) {
                $table->index(['is_setup', 'created_at']);
            }
            if (!Schema::hasIndex('clubs', 'clubs_created_by_index')) {
                $table->index('created_by');
            }
        });

        Schema::table('matches', function (Blueprint $table) {
            // Composite index cho club queries
            if (!Schema::hasIndex('matches', 'matches_club_id_status_match_date_index')) {
                $table->index(['club_id', 'status', 'match_date']);
            }
            if (!Schema::hasIndex('matches', 'matches_status_match_date_index')) {
                $table->index(['status', 'match_date']);
            }
        });

        Schema::table('events', function (Blueprint $table) {
            // Index cho events queries
            if (!Schema::hasIndex('events', 'events_club_id_start_date_index')) {
                $table->index(['club_id', 'start_date']);
            }
        });

        Schema::table('fund_transactions', function (Blueprint $table) {
            // Index cho fund queries
            if (!Schema::hasIndex('fund_transactions', 'fund_transactions_club_id_transaction_date_index')) {
                $table->index(['club_id', 'transaction_date']);
            }
            if (!Schema::hasIndex('fund_transactions', 'fund_transactions_type_transaction_date_index')) {
                $table->index(['type', 'transaction_date']);
            }
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
