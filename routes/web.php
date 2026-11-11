<?php

use App\Models\Transaction;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/pos/print/{transaction}', function (Transaction $transaction) {
        $transaction->load(['items', 'customer', 'user', 'branch']);

        return view('filament.resources.pos.receipt', [
            'transaction' => $transaction,
        ]);
    })->name('pos.print');

Route::get('/pos/scan', function () {
    return view('pos-scan');
})->name('pos.scan');
});

Route::get('/pos/payment/finish', function () {
    return view('payment-finish');
})->name('pos.finish');

Route::post('/midtrans/notification', function (Request $request, MidtransService $midtrans) {
    $handled = $midtrans->handleNotification($request->all());

    return response()->json([
        'status' => $handled ? 'ok' : 'ignored',
    ]);
})->name('midtrans.notification');
