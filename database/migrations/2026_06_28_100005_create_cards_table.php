<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('ai');          // ai | manual
            $table->text('spanish');
            $table->text('english');
            $table->string('test_direction')->default('es_to_en');
            $table->json('uses_concepts');   // [{ "type": "verb", "id": 12 }, ...]
            $table->json('must_match');      // { "tense": "...", "subject": "...", "gender": null }
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
