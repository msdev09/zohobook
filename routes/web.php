<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ZohoAuthController;

Route::get('/', function () {
    return redirect()->route('report.index');
});

Route::controller(ZohoAuthController::class)->group(function () {
    Route::get('/zoho/connect', 'connect')->name('zoho.connect');
    Route::get('/zoho/callback', 'callback')->name('zoho.callback');
});

Route::controller(ReportController::class)->group(function () {
    Route::get('/report', 'index')->name('report.index');
    Route::get('/attachments/{documentId}/download', 'downloadAttachment')->name('attachment.download');
});
