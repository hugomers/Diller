<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProvidersController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\ProductsController;

Route::prefix('providers')->group(function(){
    Route::get('/',[ProvidersController::class,'index']);
    Route::post('/refresh',[ProvidersController::class,'refreshProvider']);
    Route::get('/replyProvider',[ProvidersController::class,'replyProvider']);
});

Route::prefix('clients')->group(function(){
    Route::get('/',[ClientsController::class,'index']);
    Route::post('/refresh',[ClientsController::class,'refreshClients']);
    Route::get('/replyClient',[ClientsController::class,'replyClient']);
});

Route::prefix('products')->group(function(){
    Route::get('/',[ProductsController::class,'index']);
    Route::get('/replyClient',[ProductsController::class,'replyProducts']);
});