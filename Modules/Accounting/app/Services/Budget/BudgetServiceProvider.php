<?php

namespace Modules\Accounting\Services\Budget;

use Illuminate\Support\ServiceProvider;

/**
 * BudgetServiceProvider
 *
 * Service provider for registering all budget-related services
 */
class BudgetServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register individual services
        $this->app->singleton(GrowthService::class, function () {
            return new GrowthService();
        });

        $this->app->singleton(CapacityService::class, function () {
            return new CapacityService();
        });

        $this->app->singleton(CollectionService::class, function () {
            return new CollectionService();
        });

        $this->app->singleton(ResultService::class, function () {
            return new ResultService();
        });

        $this->app->singleton(PersonnelService::class, function () {
            return new PersonnelService();
        });

        $this->app->singleton(ExpenseService::class, function () {
            return new ExpenseService();
        });

        $this->app->singleton(FinalizationService::class, function ($app) {
            return new FinalizationService(
                $app->make(ResultService::class),
                $app->make(PersonnelService::class),
                $app->make(ExpenseService::class),
            );
        });

        // Register main BudgetService with all dependencies
        $this->app->singleton(BudgetService::class, function ($app) {
            return new BudgetService(
                $app->make(GrowthService::class),
                $app->make(CapacityService::class),
                $app->make(CollectionService::class),
                $app->make(ResultService::class),
                $app->make(PersonnelService::class),
                $app->make(ExpenseService::class),
            );
        });
    }

    /**
     * Boot services
     */
    public function boot(): void
    {
        // Service boot logic if needed
    }
}
