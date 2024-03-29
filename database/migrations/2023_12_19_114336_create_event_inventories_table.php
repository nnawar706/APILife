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
        Schema::create('event_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('inventory_category_id')->constrained('inventory_categories')->onDelete('restrict');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('restrict');
            $table->string('title', 200);
            $table->integer('quantity');
            $table->string('notes', 300)->nullable();
            $table->tinyInteger('approval_status', false, true)->default(0)->comment('1:approved,2:denied');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_inventories');
    }
};
