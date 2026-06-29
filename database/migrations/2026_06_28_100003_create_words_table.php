<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('words', function (Blueprint $table) {
            $table->id();
            $table->string('spanish');
            $table->string('english');
            $table->string('category');          // pronoun|connector|adverb|noun|adjective|question
            $table->string('gender')->nullable(); // m | f | null
            $table->string('role');              // target | ingredient
            $table->boolean('unlocked')->default(false);
            $table->timestamps();

            $table->index(['category', 'role', 'unlocked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('words');
    }
};
