<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerGroupController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/customer-groups', function () {
    return view('customer-groups.index');
})->name('customer-groups.index');

Route::prefix('api')->group(function () {
    Route::get('/customer-groups/all', [CustomerGroupController::class, 'all']);
    Route::apiResource('/customer-groups', CustomerGroupController::class);
    Route::post('/customer-groups/{id}/restore', [CustomerGroupController::class, 'restore']);
});
