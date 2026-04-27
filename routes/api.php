<?php

use App\Http\Controllers\BlastController;
use App\Http\Controllers\BlastMonitoringController;
use App\Http\Controllers\NumberCheckerController;
use Illuminate\Support\Facades\Route;

Route::post('/blast/send', [BlastController::class, 'send']);
Route::get('/blast/jobs', [BlastMonitoringController::class, 'index']);
Route::post('/number/check', [NumberCheckerController::class, 'check']);
