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
        Schema::create('user_loans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('selected_user_id')->constrained('users')->onDelete('restrict');
            $table->float('amount');
            $table->tinyInteger('type', false, true)->comment('1:debit, 2: credit');
            $table->tinyInteger('loan_type', false, true)->comment('1:lend, 2: return');
            $table->string('notes', 300)->nullable();
            $table->tinyInteger('status', false, true)->default(0)->comment('0:initiated,1:accepted,2:declined,3:paid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_loans');
    }
};
