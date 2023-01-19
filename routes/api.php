<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProvidersController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\AgentsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\CategoriesController;

Route::prefix('providers')->group(function(){
    Route::get('/',[ProvidersController::class,'index']);
    Route::post('/refresh',[ProvidersController::class,'refreshProvider']);
    Route::get('/replyProvider',[ProvidersController::class,'replyProvider']);
});

Route::prefix('clients')->group(function(){
    Route::get('/',[ClientsController::class,'index']);
    Route::post('/refresh',[ClientsController::class,'refreshClients']);
    Route::get('/replyClient',[ClientsController::class,'replyClient']);
    Route::get('/conditionSpecial',[ClientsController::class,'conditionSpecial']);
    Route::get('/refreshLoyaltyCard',[ClientsController::class,'refreshLoyaltyCard']);
});

Route::prefix('products')->group(function(){
    Route::get('/',[ProductsController::class,'index']);
    Route::get('/pairing',[ProductsController::class,'pairingProducts']);
    Route::get('/replaceProducts',[ProductsController::class,'replaceProducts']);
    Route::post('/missing',[ProductsController::class,'missingProducts']);
    Route::get('/highProducts',[ProductsController::class,'highProducts']);
    Route::get('/highPrices',[ProductsController::class,'highPrices']);
    Route::get('/highPueblaInvoice',[ProductsController::class,'highPueblaInvoice']);
    Route::get('/highPueblaProducts',[ProductsController::class,'highPueblaProducts']);
    Route::get('/replyProducts',[ProductsController::class,'replyProducts']);
    Route::get('/additionalsBarcode',[ProductsController::class,'additionalsBarcode']);
});

Route::prefix('agents')->group(function(){
    Route::get('/',[AgentsController::class,'index']);
    Route::get('/replyAgents',[AgentsController::class,'replyAgents']);
    Route::get('/createuser',[AgentsController::class,'createuser']);
});

Route::prefix('users')->group(function(){
    Route::get('/createuser',[UsersController::class,'createuser']);
});

Route::prefix('categories')->group(function(){
    Route::get('/',[CategoriesController::class,'index']);
    Route::get('/test',[CategoriesController::class,'test']);
});