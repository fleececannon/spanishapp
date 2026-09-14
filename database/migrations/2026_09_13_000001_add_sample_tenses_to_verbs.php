<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verbs', function (Blueprint $table) {
            // Tenses on a drill-all-forms verb that should get ONE sampled card
            // instead of every person. Third state of the tense cell in the grid.
            $table->json('sample_tenses')->nullable()->after('drill_all_forms');
        });
    }

    public function down(): void
    {
        Schema::table('verbs', function (Blueprint $table) {
            $table->dropColumn('sample_tenses');
        });
    }
};
