<?php

use App\Http\Controllers\AppController;
use Illuminate\Support\Facades\Route;

// Your SPA entry point
Route::get('/{any}', [AppController::class, 'index'])->where('any', '.*');
