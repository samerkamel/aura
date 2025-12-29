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
        Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('to_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('transfer_date');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['transfer_date']);
            $table->index(['from_account_id', 'to_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_transfers');
    }
};
