<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\ExpenseCategory;
use App\Models\UserLoan;
use App\Observers\EventCategoryObserver;
use App\Observers\EventObserver;
use App\Observers\ExpenseCategoryObserver;
use App\Observers\UserLoanObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        Event::observe(EventObserver::class);
        UserLoan::observe(UserLoanObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
