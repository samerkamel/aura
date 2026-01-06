<?php

namespace Modules\Invoicing\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Invoicing\Models\Invoice;
use Modules\Invoicing\Observers\InvoiceObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * The model observers for your application.
     *
     * @var array<string, array<int, string>>
     */
    protected $observers = [
        Invoice::class => [InvoiceObserver::class],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
