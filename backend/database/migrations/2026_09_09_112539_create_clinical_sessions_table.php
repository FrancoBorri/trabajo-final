<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clinical_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('clinical_history_id')
                ->constrained('clinical_histories')
                ->restrictOnDelete();

            $table->foreignId('appointment_id')
                ->nullable()
                ->constrained('appointments')
                ->restrictOnDelete();

            $table->text('topic')->nullable();
            $table->integer('session_number')->nullable();
            $table->text('evolution')->nullable();
            $table->text('observations')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_sessions');
    }
};
