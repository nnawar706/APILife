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
        Schema::create('treasurer_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasurer_id')->constrained('treasurers')->onDelete('restrict');
            $table->foreignId('event_id')->unique()->constrained('events')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasurer_events');
    }
};
