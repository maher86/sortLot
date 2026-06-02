<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PreferenceController;
use App\Http\Controllers\Api\SupplierController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    DB::connection()->getPdo();
    Redis::connection()->ping();

    return response()->json([
        'status' => 'ok',
        'db' => 'ok',
        'redis' => 'ok',
        'queue' => config('queue.default'),
    ]);
});

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/password', [AuthController::class, 'updatePassword']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/preferences', [PreferenceController::class, 'index']);
    Route::patch('/preferences', [PreferenceController::class, 'update']);
    Route::get('/preferences/pricing-tiers', [PreferenceController::class, 'pricingTiers']);
    Route::post('/preferences/pricing-tiers', [PreferenceController::class, 'storePricingTier']);
    Route::patch('/preferences/pricing-tiers/{pricingTier}', [PreferenceController::class, 'updatePricingTier']);
    Route::delete('/preferences/pricing-tiers/{pricingTier}', [PreferenceController::class, 'destroyPricingTier']);
    Route::get('/preferences/item-types', [PreferenceController::class, 'itemTypes']);
    Route::post('/preferences/item-types', [PreferenceController::class, 'storeItemType']);
    Route::patch('/preferences/item-types/{itemType}', [PreferenceController::class, 'updateItemType']);
    Route::delete('/preferences/item-types/{itemType}', [PreferenceController::class, 'destroyItemType']);

    Route::post('/suppliers/{supplier}/restore', [SupplierController::class, 'restore']);
    Route::get('/suppliers/{supplier}/purchase-orders', [SupplierController::class, 'purchaseOrders']);
    Route::get('/suppliers/{supplier}/packages', [SupplierController::class, 'packages']);
    Route::apiResource('suppliers', SupplierController::class);

    Route::post('/customers/{customer}/restore', [CustomerController::class, 'restore']);
    Route::get('/customers/{customer}/sales-orders', [CustomerController::class, 'salesOrders']);
    Route::get('/customers/{customer}/statement', [CustomerController::class, 'statement']);
    Route::apiResource('customers', CustomerController::class);

    Route::get('/sales-orders', [InvoiceController::class, 'salesOrders']);
    Route::post('/sales-orders', [InvoiceController::class, 'storeSalesOrder']);
    Route::get('/sales-orders/{invoice}', [InvoiceController::class, 'show']);
    Route::patch('/sales-orders/{invoice}', [InvoiceController::class, 'update']);
    Route::delete('/sales-orders/{invoice}', [InvoiceController::class, 'destroy']);
    Route::patch('/sales-orders/{invoice}/confirm', [InvoiceController::class, 'confirm']);
    Route::patch('/sales-orders/{invoice}/cancel', [InvoiceController::class, 'cancel']);
    Route::post('/sales-orders/{invoice}/credit-note', [InvoiceController::class, 'creditNote']);
    Route::get('/sales-orders/{invoice}/pdf', [InvoiceController::class, 'pdf']);
    Route::post('/sales-orders/{invoice}/send-email', [InvoiceController::class, 'sendEmail']);

    Route::get('/credit-notes', [InvoiceController::class, 'creditNotes']);
    Route::get('/credit-notes/{invoice}', [InvoiceController::class, 'show']);
    Route::get('/credit-notes/{invoice}/pdf', [InvoiceController::class, 'pdf']);
    Route::post('/credit-notes/{invoice}/send-email', [InvoiceController::class, 'sendEmail']);

    Route::get('/purchase-orders', [InvoiceController::class, 'purchaseOrders']);
    Route::post('/purchase-orders', [InvoiceController::class, 'storePurchaseOrder']);
    Route::get('/purchase-orders/{invoice}', [InvoiceController::class, 'show']);
    Route::patch('/purchase-orders/{invoice}', [InvoiceController::class, 'update']);
    Route::delete('/purchase-orders/{invoice}', [InvoiceController::class, 'destroy']);
    Route::patch('/purchase-orders/{invoice}/confirm', [InvoiceController::class, 'confirm']);
    Route::patch('/purchase-orders/{invoice}/cancel', [InvoiceController::class, 'cancel']);
    Route::get('/purchase-orders/{invoice}/pdf', [InvoiceController::class, 'pdf']);
    Route::post('/purchase-orders/{invoice}/send-email', [InvoiceController::class, 'sendEmail']);

    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);

    Route::get('/payments/{payment}/pdf', [PaymentController::class, 'pdf']);
    Route::post('/payments/{payment}/send-email', [PaymentController::class, 'sendEmail']);
    Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show', 'destroy']);

    Route::get('/items/sku/{sku}', [ItemController::class, 'findBySku']);
    Route::get('/items/barcode/{barcode}', [ItemController::class, 'findByBarcode']);
    Route::patch('/items/{item}/status', [ItemController::class, 'changeStatus']);
    Route::apiResource('items', ItemController::class);

    Route::patch('/packages/{package}/status', [PackageController::class, 'changeStatus']);
    Route::post('/packages/{package}/items/bulk', [PackageController::class, 'bulkItems']);
    Route::apiResource('packages', PackageController::class);
});
