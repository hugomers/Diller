<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProvidersController;

Route::prefix('providers')->group(function(){
    Route::get('/',[ProvidersController::class,'index']);
    Route::post('/refresh',[ProvidersController::class,'refreshProvider']);
    Route::get('/replyProvider',[ProvidersController::class,'replyProvider']);
});