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
        Schema::create('user_story_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_story_id')->constrained('user_stories')->onDelete('cascade');
            $table->foreignId('seen_by')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('reaction_id', false, true)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_story_views');
    }
};
