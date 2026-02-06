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
        Schema::create('period_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('cycle_length');
            $table->integer('period_duration');
            $table->date('last_period_date');
            $table->integer('age');
            $table->date('calculated_ovulation_date')->nullable();
            $table->date('calculated_next_period_date')->nullable();
            $table->json('calculation_data')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('last_period_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_records');
    }
};