<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Middleware\AdminAuthMiddleware;
use App\Http\Middleware\CheckSanctumToken;
use App\Http\Controllers\AppApi\PropertyControllerApp;
use App\Http\Controllers\AppApi\PropertyAssesmentInsertYearly;

Route::post('login',[AuthController::class,'login']);

Route::middleware([CheckSanctumToken::class])->group(function () {
    Route::post('/admin/logout', [AuthController::class, 'logout']);
    Route::post('/admin/profile', [AuthController::class, 'profile']);

    Route::any('/admin/property-listing',[PropertyController::class,'index']);
    Route::post('/property/details',[PropertyController::class,'propertyDetails']);

    // app 
    Route::post('/property/save',[PropertyControllerApp::class,'propertySave']);
    Route::post('/property-assessment/save/yearly',[PropertyAssesmentInsertYearly::class,'propertyAssessmentSaveYearly']);
    Route::post('/property-assessment/update',[PropertyAssesmentInsertYearly::class,'propertyAssessmentUpdate']);

});


