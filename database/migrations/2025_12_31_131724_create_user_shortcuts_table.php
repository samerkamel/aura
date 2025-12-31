<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_shortcuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');                    // Display name
            $table->string('url');                     // Target URL
            $table->string('icon')->nullable();        // Icon class (ti ti-xxx)
            $table->string('subtitle')->nullable();    // Short description
            $table->string('slug')->nullable();        // Menu slug for permission validation
            $table->json('required_roles')->nullable(); // Roles needed to access this shortcut
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'url']);
            $table->index(['user_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_shortcuts');
    }
};
