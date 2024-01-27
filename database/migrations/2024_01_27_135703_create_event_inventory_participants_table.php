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
        Schema::create('event_inventory_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_inventory_id')->constrained('event_inventories')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->tinyInteger('approval_status', false, true)->default(0)->comment('1:approved');

            $table->unique(['event_inventory_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_inventory_participants');
    }
};
