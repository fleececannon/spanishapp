<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kid_id')->constrained()->cascadeOnDelete();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->date('due');
            $table->unsignedInteger('interval_days')->default(0);
            $table->decimal('ease', 4, 2)->default(2.50);
            $table->unsignedInteger('reps')->default(0);
            $table->unsignedInteger('lapses')->default(0);
            $table->string('last_result')->nullable(); // pass | needs_work
            $table->timestamp('last_reviewed')->nullable();
            $table->timestamps();

            $table->unique(['kid_id', 'card_id']);
            $table->index(['kid_id', 'due']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_states');
    }
};
