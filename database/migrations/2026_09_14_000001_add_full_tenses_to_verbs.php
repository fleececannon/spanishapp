<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verbs', function (Blueprint $table) {
            // Per-tense "every person" decision. A tense that is enabled but not
            // listed here gets one sampled card. Replaces the row-level drill flag
            // as the thing the grid edits (the flag column stays, unused).
            $table->json('full_tenses')->nullable()->after('enabled_tenses');
        });

        // Carry existing settings over: drill-all verbs drill every enabled
        // conjugated tense, minus any the grid had already dashed.
        foreach (DB::table('verbs')->get() as $verb) {
            $enabled = json_decode($verb->enabled_tenses ?? '[]', true) ?: [];
            $sampled = json_decode($verb->sample_tenses ?? '[]', true) ?: [];

            $full = $verb->drill_all_forms
                ? array_values(array_diff($enabled, ['infinitive'], $sampled))
                : [];

            DB::table('verbs')->where('id', $verb->id)->update(['full_tenses' => json_encode($full)]);
        }

        Schema::table('verbs', function (Blueprint $table) {
            $table->dropColumn('sample_tenses');
        });
    }

    public function down(): void
    {
        Schema::table('verbs', function (Blueprint $table) {
            $table->json('sample_tenses')->nullable();
            $table->dropColumn('full_tenses');
        });
    }
};
