<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\CurrencyRateController;
use App\Http\Controllers\CustomsDeclarationController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\ProductMarketPriceController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\ShippingMethodController;
use App\Http\Controllers\TaxRuleController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('me');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [AuthController::class, 'changePassword'])->name('password.change');

    Route::apiResource('/users', UserController::class)->names('users');
    Route::put('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::get('/roles/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
    Route::apiResource('/roles', RoleController::class)->names('roles');

    Route::apiResource('/markets', MarketController::class)->names('markets');
    Route::put('/markets/{market}/toggle-status', [MarketController::class, 'toggleStatus'])->name('markets.toggle-status');

    Route::apiResource('/warehouses', WarehouseController::class)->names('warehouses');
    Route::put('/warehouses/{warehouse}/toggle-status', [WarehouseController::class, 'toggleStatus'])->name('warehouses.toggle-status');

    Route::apiResource('/shipping-methods', ShippingMethodController::class)->names('shipping-methods');
    Route::post('/shipping-methods/{shippingMethod}/calculate', [ShippingMethodController::class, 'calculate'])->name('shipping-methods.calculate');

    Route::apiResource('/currency-rates', CurrencyRateController::class)->names('currency-rates');
    Route::get('/currency-rates/latest/pair', [CurrencyRateController::class, 'latest'])->name('currency-rates.latest');
    Route::post('/currency-rates/convert', [CurrencyRateController::class, 'convert'])->name('currency-rates.convert');

    Route::apiResource('/tax-rules', TaxRuleController::class)->names('tax-rules');
    Route::post('/tax-rules/calculate', [TaxRuleController::class, 'calculate'])->name('tax-rules.calculate');

    Route::apiResource('/product-market-prices', ProductMarketPriceController::class)->names('product-market-prices');

    Route::apiResource('/shipments', ShipmentController::class)->names('shipments');
    Route::put('/shipments/{shipment}/status', [ShipmentController::class, 'updateStatus'])->name('shipments.update-status');

    Route::apiResource('/customs-declarations', CustomsDeclarationController::class)->names('customs-declarations');
    Route::put('/customs-declarations/{customsDeclaration}/status', [CustomsDeclarationController::class, 'updateStatus'])->name('customs-declarations.update-status');

    Route::get('/customer-groups/tree', [CustomerGroupController::class, 'tree'])->name('customer-groups.tree');
    Route::apiResource('/customer-groups', CustomerGroupController::class)->names('customer-groups');
    Route::put('/customer-groups/{customerGroup}/toggle-status', [CustomerGroupController::class, 'toggleStatus'])->name('customer-groups.toggle-status');
    Route::post('/customer-groups/{customerGroup}/attach-users', [CustomerGroupController::class, 'attachUsers'])->name('customer-groups.attach-users');
    Route::post('/customer-groups/{customerGroup}/detach-users', [CustomerGroupController::class, 'detachUsers'])->name('customer-groups.detach-users');
    Route::post('/customer-groups/{customerGroup}/attach-distributors', [CustomerGroupController::class, 'attachDistributors'])->name('customer-groups.attach-distributors');
    Route::post('/customer-groups/{customerGroup}/detach-distributors', [CustomerGroupController::class, 'detachDistributors'])->name('customer-groups.detach-distributors');
});
