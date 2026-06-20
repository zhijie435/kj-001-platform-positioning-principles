<?php

namespace App\Providers;

use App\Models\CustomsDeclaration;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Observers\CustomsDeclarationObserver;
use App\Observers\OrderObserver;
use App\Observers\PaymentObserver;
use App\Observers\ShipmentObserver;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ShipmentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerRepositories();
    }

    public function boot(): void
    {
        Order::observe(OrderObserver::class);
        Payment::observe(PaymentObserver::class);
        Shipment::observe(ShipmentObserver::class);
        CustomsDeclaration::observe(CustomsDeclarationObserver::class);

        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Shipment::class, ShipmentPolicy::class);
    }

    protected function registerRepositories(): void
    {
        $this->app->singleton(
            \App\Contracts\RepositoryInterface::class . '@Order',
            \App\Repositories\OrderRepository::class
        );

        $this->app->singleton(
            \App\Contracts\RepositoryInterface::class . '@Payment',
            \App\Repositories\PaymentRepository::class
        );

        $this->app->singleton(
            \App\Contracts\RepositoryInterface::class . '@Shipment',
            \App\Repositories\ShipmentRepository::class
        );

        $this->app->singleton(
            \App\Contracts\RepositoryInterface::class . '@CustomsDeclaration',
            \App\Repositories\CustomsDeclarationRepository::class
        );
    }
}
