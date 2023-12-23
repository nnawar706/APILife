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
        Schema::create('treasurer_liabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasurer_id')->constrained('treasurers')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
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
        Schema::dropIfExists('treasurer_liabilities');
    }
};
