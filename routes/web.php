<?php

use App\Http\Controllers\KycController;
use Illuminate\Support\Facades\Route;

Route::get('/kyc/scan', [KycController::class, 'showScanForm'])->name('kyc.scan-form');
Route::post('/kyc/scan', [KycController::class, 'scanId'])->name('kyc.scan-id');
