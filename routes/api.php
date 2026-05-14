<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

Route::controller(ReportController::class)->group(function () {
    Route::post('/sync', 'sync');
    Route::post('/budgets', 'saveBudget');
    Route::get('/transactions', 'transactions');
});
