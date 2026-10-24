<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('predecessor_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('successor_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('type', 2)->default('FS');
            $table->unsignedInteger('lag')->default(0);
            $table->timestamps();

            $table->unique(['predecessor_id', 'successor_id']);
            $table->index('successor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dependencies');
    }
};
