<?php

use App\Http\Controllers\BlastController;
use Illuminate\Support\Facades\Route;

Route::post('/blast/send', [BlastController::class, 'send']);
