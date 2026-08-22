<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('position')->unique(); // "Lesson 1", "Lesson 2", ...
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->unsignedSmallInteger('minutes')->nullable();
            $table->longText('body'); // HTML fragment rendered on the lesson sheet
            $table->timestamps();
        });

        // Ship Lesson 1 with the table. Data inserts normally live in seeders,
        // but lesson content is prod-managed (like words): re-running a seeder
        // on deploy would overwrite edits made in the admin, so it runs once here.
        $lessonOne = database_path('data/lessons/lesson-1.html');

        if (file_exists($lessonOne)) {
            DB::table('lessons')->insert([
                'position' => 1,
                'title' => '¿Quieres comer?',
                'subtitle' => 'Food talk with querer/tener/necesitar, then feelings and places with estar.',
                'minutes' => 20,
                'body' => file_get_contents($lessonOne),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
