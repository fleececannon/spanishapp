<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('words', function (Blueprint $table) {
            $table->boolean('vocab_card')->default(false);
        });

        Schema::table('verbs', function (Blueprint $table) {
            $table->boolean('vocab_card')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('words', function (Blueprint $table) {
            $table->dropColumn('vocab_card');
        });

        Schema::table('verbs', function (Blueprint $table) {
            $table->dropColumn('vocab_card');
        });
    }
};
