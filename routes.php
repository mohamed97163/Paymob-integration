<?php

use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\Front\ProfileController;
use App\Http\Controllers\PDFController;
use App\Models\Payment;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// users routes
    Route::get('/getToken', [\App\Http\Controllers\Dashboard\PaymentController::class, 'getToken']);
    Route::post('/create-order/{tokens}', [\App\Http\Controllers\Dashboard\PaymentController::class, 'createOrder']);
Route::middleware('auth:sanctum')->group(function() {
    Route::post('/get-payment-token/{order_id}/{price}/{integration_id}/{token}/{eventID}', [\App\Http\Controllers\Dashboard\PaymentController::class, 'getPaymentToken']);
    Route::get('/success/{transaction_id}', [\App\Http\Controllers\Dashboard\PaymentController::class, 'checkVerifyPaymentStatus']);
});
    Route::get('/callback', [\App\Http\Controllers\Dashboard\PaymentController::class, 'callback'])->name('callback'); // this route get all reponse data to paymob
