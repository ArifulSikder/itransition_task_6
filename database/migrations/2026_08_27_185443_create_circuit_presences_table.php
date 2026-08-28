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
        Schema::create('circuit_presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circuit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('color', 16);
            $table->integer('cursor_x')->nullable();
            $table->integer('cursor_y')->nullable();
            $table->timestamp('last_seen_at')->index();
            $table->timestamps();

            $table->unique(['circuit_id', 'participant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuit_presences');
    }
};
