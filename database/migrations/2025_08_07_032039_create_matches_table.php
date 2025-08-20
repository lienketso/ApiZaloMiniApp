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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('match_date');
            $table->string('time')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['upcoming', 'ongoing', 'completed'])->default('upcoming');
            $table->decimal('bet_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            // Foreign keys
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['club_id', 'status']);
            $table->index('match_date');
        });

        // Bảng teams (đội)
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('match_id');
            $table->string('name');
            $table->integer('score')->nullable();
            $table->boolean('is_winner')->default(false);
            $table->timestamps();

            // Foreign key
            $table->foreign('match_id')->references('id')->on('matches')->onDelete('cascade');
        });

        // Bảng team_players (cầu thủ trong đội)
        Schema::create('team_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Đảm bảo mỗi user chỉ có trong 1 team của 1 match
            $table->unique(['team_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_players');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('matches');
    }
};
