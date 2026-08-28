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
        Schema::create('circuit_wires', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('circuit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_node_id')->constrained('circuit_nodes')->cascadeOnDelete();
            $table->unsignedTinyInteger('from_port')->default(0);
            $table->foreignId('to_node_id')->constrained('circuit_nodes')->cascadeOnDelete();
            $table->unsignedTinyInteger('to_port')->default(0);
            $table->timestamps();

            $table->unique(['circuit_id', 'to_node_id', 'to_port']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuit_wires');
    }
};
