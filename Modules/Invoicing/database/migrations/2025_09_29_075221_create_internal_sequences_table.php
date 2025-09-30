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
        Schema::create('internal_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('prefix')->nullable();
            $table->string('format'); // e.g., 'INT-{YEAR}-{NUMBER}', 'IBT{NUMBER}'
            $table->unsignedInteger('current_number')->default(0);
            $table->unsignedInteger('starting_number')->default(1);
            $table->json('sector_ids')->nullable(); // JSON array of sector IDs, null = all sectors
            $table->unsignedBigInteger('business_unit_id')->nullable(); // null = all business units
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('business_unit_id')->references('id')->on('business_units')->onDelete('cascade');
            $table->index(['is_active']);
            $table->index(['business_unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_sequences');
    }
};