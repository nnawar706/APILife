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
        Schema::create('event_participant_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_participant_id')->constrained('event_participants')->onDelete('restrict');
            $table->float('amount');
            $table->tinyInteger('status', false, true)->default(0)->comment('1:completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_participant_payments');
    }
};
