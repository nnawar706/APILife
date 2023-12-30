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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('added_by_user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('event_category_id')->constrained('event_categories')->onDelete('restrict');
            $table->foreignId('lead_user_id')->constrained('users')->onDelete('restrict');
            $table->string('title', 150);
            $table->text('detail');
            $table->dateTime('from_date');
            $table->dateTime('to_date')->nullable();
            $table->string('remarks', 500)->nullable();
            $table->foreignId('event_status_id')->constrained('event_statuses')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
