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

    // =================== WEB =====================//
    Route::post('/admin/logout', [AuthController::class, 'logout']);
    Route::post('/admin/profile', [AuthController::class, 'profile']);


   // property list and details
    Route::any('/admin/property-listing',[PropertyController::class,'index']);
    Route::post('/property/details',[PropertyController::class,'propertyDetails']);

    // landloard edit
     Route::post('/landloard/update',[PropertyController::class,'updateLandlord']);

    // edit property
    Route::post('/property/update',[PropertyController::class,'updateProperty']);

    // edit occupency
    Route::post('/occupency/update',[PropertyController::class,'updateOccupency']);


    // edit assessment
   Route::post('/assessment/update',[PropertyController::class,'updateAssessment']);

    // edit updateGeoLocation
   Route::post('/geolocation/update',[PropertyController::class,'updateGeoLocation']);













    //===================== APP ====================// 
    Route::post('/property/save',[PropertyControllerApp::class,'propertySave']);
    Route::post('/property-assessment/save/yearly',[PropertyAssesmentInsertYearly::class,'propertyAssessmentSaveYearly']);
    Route::post('/property-assessment/update',[PropertyAssesmentInsertYearly::class,'propertyAssessmentUpdate']);


    //===================== PROPERTY-TYPE ====================// 
    Route::get('property-type-listing',[App\Http\Controllers\Api\PropertyTypeController::class,'index']);
    Route::post('property-type-insert',[App\Http\Controllers\Api\PropertyTypeController::class,'insert']);
    Route::post('property-type-update',[App\Http\Controllers\Api\PropertyTypeController::class,'update']);
    Route::get('property-type-single-view/{id}',[App\Http\Controllers\Api\PropertyTypeController::class,'single']);
    Route::get('property-type-delete/{id}',[App\Http\Controllers\Api\PropertyTypeController::class,'delete']);

    //===================== PROPERTY-CATEGORY ====================// 

    Route::get('property-categories-listing',[App\Http\Controllers\Api\PropertyCategoryController::class,'index']);
    Route::post('property-categories-insert',[App\Http\Controllers\Api\PropertyCategoryController::class,'insert']);
    Route::post('property-categories-update',[App\Http\Controllers\Api\PropertyCategoryController::class,'update']);
    Route::get('property-categories-single-view/{id}',[App\Http\Controllers\Api\PropertyCategoryController::class,'single']);
    Route::get('property-categories-delete/{id}',[App\Http\Controllers\Api\PropertyCategoryController::class,'delete']);

    //===================== COUNCIL-ADJUSTMENT ====================// 

    Route::get('council-adjustment-master-list',[App\Http\Controllers\Api\CouncilAdjustmentController::class,'index']);
    Route::post('council-adjustment-master-insert',[App\Http\Controllers\Api\CouncilAdjustmentController::class,'insert']);
    Route::post('council-adjustment-master-update',[App\Http\Controllers\Api\CouncilAdjustmentController::class,'update']);
    Route::get('council-adjustment-master-single-view/{id}',[App\Http\Controllers\Api\CouncilAdjustmentController::class,'single']);


    //===================== PROPERTY-PROPERTY-VALUE-ADDED ====================// 

    Route::get('property-property-value-added-necessary',[App\Http\Controllers\Api\PropertyPropertyValueController::class,'value']);
    Route::post('property-assessment-dependency',[App\Http\Controllers\Api\PropertyPropertyValueController::class,'dependency']);
    Route::get('property-property-value-list',[App\Http\Controllers\Api\PropertyPropertyValueController::class,'list']);
    Route::post('property-property-value-insert',[App\Http\Controllers\Api\PropertyPropertyValueController::class,'insert']);
    
    //===================== PROPERTY-VALUE-MATERIALS ====================// 

    Route::get('property-roof-materials',[App\Http\Controllers\Api\PropertyValueMaterialController::class,'index']);
    Route::post('property-roof-materials-insert-data',[App\Http\Controllers\Api\PropertyValueMaterialController::class,'insert']);
    Route::post('property-roof-materials-update-data',[App\Http\Controllers\Api\PropertyValueMaterialController::class,'update']);

    //===================== PROPERTY-WALL-MATERIALS ====================// 

    Route::get('property-wall-materials',[App\Http\Controllers\Api\PropertyWallMaterialController::class,'index']);
    Route::post('property-wall-materials-insert-data',[App\Http\Controllers\Api\PropertyWallMaterialController::class,'insert']);
    Route::post('property-wall-materials-update-data',[App\Http\Controllers\Api\PropertyWallMaterialController::class,'update']);

    //===================== PROPERTY-USE ====================// 

   Route::get('property-use-api',[App\Http\Controllers\Api\PropertyUseController::class,'index']);
    Route::post('property-use-api-insert-data',[App\Http\Controllers\Api\PropertyUseController::class,'insert']);
    Route::post('property-use-api-update-data',[App\Http\Controllers\Api\PropertyUseController::class,'update']);

    //===================== PROPERTY-ZONE ====================// 

    Route::get('property-zone-api',[App\Http\Controllers\Api\PropertyZoneController::class,'index']);
    Route::post('property-zone-api-insert-data',[App\Http\Controllers\Api\PropertyZoneController::class,'insert']);
    Route::post('property-zone-api-update-data',[App\Http\Controllers\Api\PropertyZoneController::class,'update']);

    //===================== SWIMMINGS-API ====================// 
    Route::get('swimming-api',[App\Http\Controllers\Api\SwimmingController::class,'index']);
    Route::post('swimming-api-insert-data',[App\Http\Controllers\Api\SwimmingController::class,'insert']);
    Route::post('swimming-api-update-data',[App\Http\Controllers\Api\SwimmingController::class,'update']);

    //===================== PROPERTY-WINDOW-TYPES ====================// 

    Route::get('property-window-types',[App\Http\Controllers\Api\PropertyWindowTypeController::class,'index']);
    Route::post('property-window-types-insert-data',[App\Http\Controllers\Api\PropertyWindowTypeController::class,'insert']);
    Route::post('property-window-types-update-data',[App\Http\Controllers\Api\PropertyWindowTypeController::class,'update']);

    // property-value-added
    
    Route::get('property-value-added',[App\Http\Controllers\Api\PropertyValueAddController::class,'index']);
    Route::post('property-value-added-insert-data',[App\Http\Controllers\Api\PropertyValueAddController::class,'insert']);
    Route::post('property-value-added-update-data',[App\Http\Controllers\Api\PropertyValueAddController::class,'update']);

    // property_inaccessibles
    Route::get('property-inaccessibles',[App\Http\Controllers\Api\PropertyInaccessibleController::class,'index']);
    Route::post('property-inaccessibles-insert-data',[App\Http\Controllers\Api\PropertyInaccessibleController::class,'insert']);
    Route::post('property-inaccessibles-update-data',[App\Http\Controllers\Api\PropertyInaccessibleController::class,'update']);

    // assesment-app-user
    Route::get('listing-assement-app-user',[App\Http\Controllers\Api\User\AssesmentAppUserController::class,'listing']);
    Route::post('listing-assement-app-user/insert-data',[App\Http\Controllers\Api\User\AssesmentAppUserController::class,'insert']);
    Route::get('listing-assement-app-user/edit-data/{id}',[App\Http\Controllers\Api\User\AssesmentAppUserController::class,'edit']);
    Route::post('listing-assement-app-user/update-data',[App\Http\Controllers\Api\User\AssesmentAppUserController::class,'update']);
    Route::get('listing-assement-app-user/delete-data/{id}',[App\Http\Controllers\Api\User\AssesmentAppUserController::class,'destroy']);

    // manage-system-user
    Route::get('listing-system-user',[App\Http\Controllers\Api\User\SystemUserController::class,'listing']);
    Route::post('listing-system-user/insert-data',[App\Http\Controllers\Api\User\SystemUserController::class,'insert']);
    Route::get('listing-system-user/edit-data/{id}',[App\Http\Controllers\Api\User\SystemUserController::class,'edit']);
    Route::post('listing-system-user/update-data',[App\Http\Controllers\Api\User\SystemUserController::class,'update']);
    Route::get('listing-system-user/delete-data/{id}',[App\Http\Controllers\Api\User\SystemUserController::class,'delete']);
    Route::get('get-roles-listing',[App\Http\Controllers\Api\User\SystemUserController::class,'getRoleListing']);

});


