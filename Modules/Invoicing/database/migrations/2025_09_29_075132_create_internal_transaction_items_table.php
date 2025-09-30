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
        Schema::create('internal_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('internal_transaction_id');
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->string('account_code')->nullable();
            $table->string('cost_center')->nullable();
            $table->string('project_reference')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->foreign('internal_transaction_id', 'internal_transaction_items_transaction_id_foreign')
                  ->references('id')
                  ->on('internal_transactions')
                  ->onDelete('cascade');

            $table->index(['internal_transaction_id'], 'internal_transaction_items_transaction_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_transaction_items');
    }
};