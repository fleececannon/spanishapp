<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('my_verbs', function (Blueprint $table) {
            $table->id();
            $table->string('spanish')->unique();
            $table->string('english');
            $table->unsignedTinyInteger('frequency_score')->default(50)->index();
            $table->boolean('unlocked')->default(false)->index();  // in training (has a card in the queue)
            $table->boolean('mastered')->default(false)->index();  // already known: out of the queue, counted in the tally
            // SRS state, same shape and tuning as the kids' review_states.
            $table->date('due')->nullable()->index();
            $table->unsignedInteger('interval_days')->default(0);
            $table->decimal('ease', 4, 2)->nullable();
            $table->unsignedInteger('reps')->default(0);
            $table->unsignedInteger('lapses')->default(0);
            $table->string('last_result')->nullable();
            $table->timestamp('last_reviewed')->nullable();
            $table->timestamps();
        });

        // Preload the parent's verb catalog (like lessons: prod-managed content
        // shipped once with the table, never re-seeded over live edits).
        $file = database_path('data/my_verbs.json');

        if (file_exists($file)) {
            $now = now();
            $rows = collect(json_decode(file_get_contents($file), true))->map(fn (array $v) => [
                'spanish' => $v['spanish'],
                'english' => $v['english'],
                'frequency_score' => $v['frequency_score'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($rows->chunk(100) as $chunk) {
                DB::table('my_verbs')->insert($chunk->all());
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('my_verbs');
    }
};
