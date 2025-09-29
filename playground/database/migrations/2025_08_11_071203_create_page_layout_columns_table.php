<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_layout_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_layout_id')->constrained('page_layouts')->cascadeOnDelete();
            $table->unsignedInteger('index'); // 1-based position
            $table->string('key')->nullable();
            $table->json('settings')->nullable(); // width per breakpoint, align, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_layout_columns');
    }
};
