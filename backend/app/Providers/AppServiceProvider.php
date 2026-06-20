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
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Order::observe(OrderObserver::class);
        Payment::observe(PaymentObserver::class);
        Shipment::observe(ShipmentObserver::class);
        CustomsDeclaration::observe(CustomsDeclarationObserver::class);
    }
}
