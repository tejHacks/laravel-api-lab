<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // who added it
            $table->string('title', 200);
            $table->string('author', 120);
            $table->string('genre', 20)->default('fiction');
            $table->unsignedSmallInteger('published_year')->nullable();
            $table->unsignedSmallInteger('pages')->nullable();
            $table->string('isbn', 20)->nullable()->unique();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('genre');
            $table->index('published_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
