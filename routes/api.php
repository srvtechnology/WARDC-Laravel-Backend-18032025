<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Middleware\AdminAuthMiddleware;
use App\Http\Middleware\CheckSanctumToken;
use App\Http\Controllers\AppApi\PropertyControllerApp;

Route::post('login',[AuthController::class,'login']);

Route::middleware([CheckSanctumToken::class])->group(function () {
    Route::post('/admin/logout', [AuthController::class, 'logout']);
    Route::post('/admin/profile', [AuthController::class, 'profile']);

    Route::any('/admin/property-listing',[PropertyController::class,'index']);

    // app 
    Route::post('/property/save',[PropertyControllerApp::class,'propertySave']);
});


