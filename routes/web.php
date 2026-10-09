<?php


use App\Http\Controllers\CalculatorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CalculatorController::class, 'index'])->name('calc');
Route::post('/calculate', [CalculatorController::class, 'calculate'])->name('calculate');
