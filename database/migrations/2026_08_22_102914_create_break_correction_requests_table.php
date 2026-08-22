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
        Schema::create('break_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('break_id')->constrained()->cascadeOnDelete();
            $table->time('requested_break_start');
            $table->time('requested_break_end');
            $table->tinyInteger('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('break_correction_requests');
    }
};
