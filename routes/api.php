<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\FacilityController;

use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\InstallationController;
use App\Http\Controllers\Api\RolePermissionController;


Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

});



Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/roles', [RolePermissionController::class, 'index']);
    Route::get('/permissions', [RolePermissionController::class, 'permissions']);
    Route::get('/roles/{id}', [RolePermissionController::class, 'show']);
    Route::post('/roles', [RolePermissionController::class, 'store']);
    Route::put('/roles/{id}', [RolePermissionController::class, 'update']);
    Route::delete('/roles/{id}', [RolePermissionController::class, 'destroy']);
});


Route::post('/import/facilities', [FacilityController::class, 'importStatesAndFacilities']);
Route::get('/import/stats', [FacilityController::class, 'getImportStats']);

Route::post('/import/installations', [InstallationController::class, 'importInstallations']);

// Installation routes
Route::get('/installations', [InstallationController::class, 'index']);
Route::get('/installations/statistics', [InstallationController::class, 'statistics']);
Route::get('/installations/search', [InstallationController::class, 'search']);
Route::get('/installations/state/{stateId}', [InstallationController::class, 'byState']);
Route::get('/installations/facility/{facilityId}', [InstallationController::class, 'byFacility']);
Route::get('/installations/{id}', [InstallationController::class, 'show']);
Route::patch('/installations/{id}/verification', [InstallationController::class, 'updateVerification']);


Route::apiResource('warehouses', WarehouseController::class);
