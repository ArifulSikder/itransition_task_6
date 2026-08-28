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
        Schema::create('circuit_nodes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('circuit_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->integer('x');
            $table->integer('y');
            $table->string('label')->nullable();
            $table->boolean('value')->default(false);
            $table->timestamps();

            $table->index(['circuit_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuit_nodes');
    }
};
