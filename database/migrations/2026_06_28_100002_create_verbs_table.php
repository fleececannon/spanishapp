<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verbs', function (Blueprint $table) {
            $table->id();
            $table->string('spanish');           // infinitive, e.g. "Estar"
            $table->string('english');           // "to be"
            $table->string('tag')->index();      // "Key Verbs", "Verb Set 1"...
            $table->string('verb_class');        // AR | ER | IR | irregular
            $table->json('enabled_tenses');      // ["infinitive","present","past"]
            $table->boolean('drill_all_forms')->default(false);
            $table->boolean('unlocked')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verbs');
    }
};
