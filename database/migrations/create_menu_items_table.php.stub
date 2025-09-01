<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->onDelete('cascade');
            $table->string('type')->default('link');
            $table->string('icon')->nullable();
            $table->string('icon_alt')->nullable();
            $table->string('target')->default('_self');
            $table->integer('order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            
            $table->foreignId('page_id')->nullable()->constrained('pages')->onDelete('set null');
            
            $table->index(['menu_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};