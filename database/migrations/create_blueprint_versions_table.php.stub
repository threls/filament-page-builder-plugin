<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blueprint_id')->constrained('blueprints')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('schema');
            $table->string('status')->default('draft'); // draft|published|deprecated
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['blueprint_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_versions');
    }
};
