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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['cash', 'bank', 'credit_card', 'digital_wallet', 'other']);
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->decimal('starting_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->string('currency', 3)->default('EGP');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // For additional account details
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};