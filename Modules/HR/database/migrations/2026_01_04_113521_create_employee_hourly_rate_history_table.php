<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_hourly_rate_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->decimal('hourly_rate', 10, 2);
            $table->string('currency', 3)->default('EGP');
            $table->date('effective_date');
            $table->date('end_date')->nullable(); // NULL = current rate
            $table->enum('reason', ['initial', 'annual_review', 'promotion', 'adjustment', 'correction', 'other'])->default('initial');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'effective_date']);
            $table->index('effective_date');
        });

        // Migrate existing employee hourly rates to history table
        $employees = \Modules\HR\Models\Employee::whereNotNull('hourly_rate')
            ->where('hourly_rate', '>', 0)
            ->get();

        foreach ($employees as $employee) {
            DB::table('employee_hourly_rate_history')->insert([
                'employee_id' => $employee->id,
                'hourly_rate' => $employee->hourly_rate,
                'currency' => 'EGP',
                'effective_date' => $employee->start_date?->toDateString() ?? $employee->created_at?->toDateString() ?? '2024-01-01',
                'end_date' => null,
                'reason' => 'initial',
                'notes' => 'Migrated from employee hourly_rate field',
                'created_by' => 1, // System user
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_hourly_rate_history');
    }
};
