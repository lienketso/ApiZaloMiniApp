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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('clubs')->onDelete('cascade');
            $table->string('phone', 20);
            $table->string('invite_token', 255)->unique();
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'expired'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            // Index để tìm kiếm nhanh
            $table->index(['phone', 'club_id']);
            $table->index(['invite_token']);
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
