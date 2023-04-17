<?php

use App\Http\Controllers\initialization\indexController;
use Illuminate\Support\Facades\Route;

Route::get('/', [indexController::class, 'initialization']);
Route::get('/{accountId}/', [indexController::class, 'index'])->name('main');

