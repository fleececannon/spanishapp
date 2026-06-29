<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kids', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('password'); // bcrypt hash (Kid model 'hashed' cast)
            $table->unsignedInteger('daily_new_card_pace')->default(5);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kids');
    }
};
