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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('designation_id')->constrained('designations')->onDelete('restrict');
            $table->string('name', 100);
            $table->string('phone_no', 20)->unique();
            $table->string('birthday', 5);
            $table->string('photo_url', 100)->nullable();
            $table->string('password');
            $table->tinyInteger('status', false, true)->default(1)->comment('0: inactive, 1: active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
