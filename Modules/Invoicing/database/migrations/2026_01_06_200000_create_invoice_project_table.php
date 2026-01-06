<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create invoice_project pivot table for many-to-many relationship.
 *
 * This allows invoices to be allocated across multiple projects,
 * with tracking of allocated amounts per project.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->decimal('allocated_amount', 12, 2)->comment('Amount allocated to this project');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['invoice_id', 'project_id']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_project');
    }
};
