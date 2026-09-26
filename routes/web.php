<?php

use App\Http\Controllers\Auth\{AuthController,GoogleController,PasswordController,VerificationController};
use App\Http\Controllers\{CalculatorController,CatalogController,HomeController,TrackingController};
use App\Http\Controllers\Customer;
use App\Http\Controllers\Owner;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/shop', [CatalogController::class,'index'])->name('catalog.index');
Route::get('/shop/{product:slug}', [CatalogController::class,'show'])->name('catalog.show');
Route::get('/calculator', [CalculatorController::class,'index'])->name('calculator');
Route::post('/calculator', [CalculatorController::class,'calculate'])->name('calculator.calculate');
Route::get('/tracking', [TrackingController::class,'index'])->name('tracking');

Route::middleware('guest')->group(function(){
    Route::get('/login',[AuthController::class,'showLogin'])->name('login');
    Route::post('/login',[AuthController::class,'login'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register',[AuthController::class,'showRegister'])->name('register');
    Route::post('/register',[AuthController::class,'register'])->middleware('throttle:6,1')->name('register.store');
    Route::get('/auth/google',[GoogleController::class,'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback',[GoogleController::class,'callback'])->name('google.callback');
    Route::get('/forgot-password',[PasswordController::class,'forgot'])->name('password.request');
    Route::post('/forgot-password',[PasswordController::class,'sendReset'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}',[PasswordController::class,'reset'])->name('password.reset');
    Route::post('/reset-password',[PasswordController::class,'update'])->middleware('throttle:10,1')->name('password.update');
});

Route::middleware('auth')->group(function(){
    Route::post('/logout',[AuthController::class,'logout'])->name('logout');
    Route::get('/choose-mode',[AuthController::class,'showMode'])->name('auth.mode');
    Route::post('/choose-mode',[AuthController::class,'chooseMode'])->name('auth.mode.store');
    Route::get('/email/verify',[VerificationController::class,'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}',[VerificationController::class,'verify'])->middleware(['signed','throttle:8,1'])->name('verification.verify');
    Route::post('/email/verification-notification',[VerificationController::class,'resend'])->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware(['auth','verified','role:customer'])->group(function(){
    Route::get('/dashboard',Customer\DashboardController::class)->name('customer.dashboard');
    Route::get('/cart',[Customer\CartController::class,'index'])->name('cart.index');
    Route::post('/cart',[Customer\CartController::class,'store'])->name('cart.store');
    Route::patch('/cart/{variant}',[Customer\CartController::class,'update'])->name('cart.update');
    Route::delete('/cart/{variant}',[Customer\CartController::class,'destroy'])->name('cart.destroy');
    Route::get('/checkout',[Customer\CheckoutController::class,'show'])->name('checkout.show');
    Route::post('/checkout',[Customer\CheckoutController::class,'store'])->name('checkout.store');
    Route::get('/dashboard/orders',[Customer\OrderController::class,'index'])->name('customer.orders.index');
    Route::get('/dashboard/orders/{order}',[Customer\OrderController::class,'show'])->name('customer.orders.show');
    Route::get('/dashboard/invoices',[Customer\InvoiceController::class,'index'])->name('customer.invoices.index');
    Route::get('/dashboard/invoices/{invoice}',[Customer\InvoiceController::class,'show'])->name('customer.invoices.show');
    Route::get('/dashboard/pay',[Customer\PaymentController::class,'create'])->name('customer.payments.create');
    Route::post('/dashboard/pay',[Customer\PaymentController::class,'store'])->name('customer.payments.store');
    Route::get('/dashboard/profile',[Customer\ProfileController::class,'edit'])->name('customer.profile');
    Route::put('/dashboard/profile',[Customer\ProfileController::class,'update'])->name('customer.profile.update');
});

// Owner-only create/mutation routes are registered before dynamic staff routes.
Route::prefix('owner')->name('owner.')->middleware(['auth','verified','role:owner'])->group(function(){
    Route::get('/batches/create',[Owner\BatchController::class,'create'])->name('batches.create');
    Route::post('/batches',[Owner\BatchController::class,'store'])->name('batches.store');

    Route::get('/products',[Owner\ProductController::class,'index'])->name('products.index');
    Route::get('/products/create',[Owner\ProductController::class,'create'])->name('products.create');
    Route::post('/products',[Owner\ProductController::class,'store'])->name('products.store');
    Route::get('/products/{product}/edit',[Owner\ProductController::class,'edit'])->name('products.edit');
    Route::put('/products/{product}',[Owner\ProductController::class,'update'])->name('products.update');
    Route::delete('/products/{product}',[Owner\ProductController::class,'destroy'])->name('products.destroy');
    Route::post('/products/{product}/variants',[Owner\ProductController::class,'variant'])->name('products.variant');
    Route::put('/products/{product}/variants/{variant}',[Owner\ProductController::class,'updateVariant'])->name('products.variant.update');
    Route::post('/products/{product}/variants/{variant}/toggle',[Owner\ProductController::class,'toggleVariant'])->name('products.variant.toggle');
    Route::post('/products/{product}/close-po',[Owner\ProductController::class,'close'])->name('products.close');

    Route::post('/batches/{batch}/orders',[Owner\BatchController::class,'addOrder'])->name('batches.orders.store');
    Route::post('/batches/{batch}/adjustments',[Owner\BatchAdjustmentController::class,'store'])->name('batches.adjustments.store');
    Route::get('/orders',[Owner\OrderController::class,'index'])->name('orders.index');
    Route::get('/orders/{order}',[Owner\OrderController::class,'show'])->name('orders.show');
    Route::patch('/orders/{order}/status',[Owner\OrderController::class,'status'])->name('orders.status');
    Route::post('/orders/{order}/adjustments',[Owner\AdjustmentController::class,'store'])->name('orders.adjustments.store');
    Route::get('/customers',[Owner\CustomerController::class,'index'])->name('customers.index');
    Route::get('/customers/{customer}',[Owner\CustomerController::class,'show'])->name('customers.show');
    Route::post('/customers/{customer}/merge',[Owner\CustomerController::class,'merge'])->name('customers.merge');

    Route::get('/payments',[Owner\PaymentController::class,'index'])->name('payments.index');
    Route::get('/payments/{payment}',[Owner\PaymentController::class,'show'])->name('payments.show');
    Route::post('/payments/{payment}/approve',[Owner\PaymentController::class,'approve'])->name('payments.approve');
    Route::post('/payments/{payment}/reject',[Owner\PaymentController::class,'reject'])->name('payments.reject');
    Route::get('/payment-proofs/{proof}/preview',[Owner\PaymentController::class,'proofPreview'])->name('payments.proof.preview');
    Route::get('/payment-proofs/{proof}',[Owner\PaymentController::class,'proof'])->name('payments.proof');
    Route::get('/invoices',[Owner\InvoiceController::class,'index'])->name('invoices.index');
    Route::post('/invoices',[Owner\InvoiceController::class,'store'])->name('invoices.store');
    Route::get('/master',[Owner\MasterDataController::class,'index'])->name('master.index');
    Route::get('/rate',[Owner\MasterDataController::class,'rateIndex'])->name('rate.index');
    Route::get('/data',[Owner\MasterDataController::class,'dataIndex'])->name('data.index');
    Route::post('/master/country/{country?}',[Owner\MasterDataController::class,'country'])->name('master.country');
    Route::post('/master/shipping',[Owner\MasterDataController::class,'shipping'])->name('master.shipping');
    Route::post('/master/shipping/{shipping}/toggle',[Owner\MasterDataController::class,'toggleShipping'])->name('master.shipping.toggle');
    Route::post('/master/warehouse',[Owner\MasterDataController::class,'warehouse'])->name('master.warehouse');
    Route::post('/master/go',[Owner\MasterDataController::class,'group'])->name('master.go');
    Route::post('/master/bank',[Owner\MasterDataController::class,'bank'])->name('master.bank');
    Route::post('/master/bank/{bank}/toggle',[Owner\MasterDataController::class,'toggleBank'])->name('master.bank.toggle');
    Route::post('/master/status/{status?}',[Owner\MasterDataController::class,'status'])->name('master.status');
    Route::get('/admins',[Owner\AdminController::class,'index'])->name('admins.index');
    Route::post('/admins',[Owner\AdminController::class,'store'])->name('admins.store');
    Route::post('/admins/{admin}/toggle',[Owner\AdminController::class,'toggle'])->name('admins.toggle');
    Route::get('/import',[Owner\ImportController::class,'index'])->name('import.index');
    Route::post('/import/preview',[Owner\ImportController::class,'preview'])->name('import.preview');
    Route::post('/import/run',[Owner\ImportController::class,'run'])->name('import.run');
    Route::get('/import/runs/{run}',[Owner\ImportController::class,'status'])->name('import.status');
    Route::get('/import/runs/{run}/source',[Owner\ImportController::class,'source'])->name('import.source');
    Route::get('/export',[Owner\ExportController::class,'index'])->name('export.index');
    Route::post('/export/go',[Owner\ExportController::class,'go'])->name('export.go');
    Route::post('/export/full',[Owner\ExportController::class,'full'])->name('export.full');
});

Route::prefix('owner')->name('owner.')->middleware(['auth','verified','role:owner,admin'])->group(function(){
    Route::get('/',Owner\DashboardController::class)->name('dashboard');
    Route::get('/batches',[Owner\BatchController::class,'index'])->name('batches.index');
    Route::get('/batches/{batch}',[Owner\BatchController::class,'show'])->name('batches.show');
    Route::patch('/batches/{batch}/status',[Owner\BatchController::class,'updateStatus'])->name('batches.status');
    Route::get('/tracking',[Owner\TrackingController::class,'index'])->name('tracking.index');
    Route::post('/tracking',[Owner\TrackingController::class,'store'])->name('tracking.store');
    Route::patch('/tracking/{shipment}',[Owner\TrackingController::class,'update'])->name('tracking.update');
});
