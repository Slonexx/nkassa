<?php

use App\Http\Controllers\initialization\indexController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('Sicrity/Check/Check/Check', [indexController::class, 'check']);
