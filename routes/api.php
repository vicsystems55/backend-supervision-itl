<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LgaController;

use App\Http\Controllers\StateController;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\InstallationController;
use App\Http\Controllers\UserAccountsController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\InstallationAssignmentController;
use App\Http\Controllers\FacilityTechnicianAssignmentController;


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


Route::post('/importfacilities', function () {
    return response()->json(['test' => 123]);
});
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


Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('user-accounts', UserAccountsController::class);
     Route::get('user-technicians', [UserAccountsController::class, 'technicians']); // Add this line

    // Additional routes
    Route::post('user-accounts/{id}/reset-password', [UserAccountsController::class, 'resetPassword']);
    Route::post('user-accounts/{id}/assign-roles', [UserAccountsController::class, 'assignRoles']);
    Route::post('user-accounts/{id}/assign-permissions', [UserAccountsController::class, 'assignPermissions']);
    Route::get('user-accounts/{id}/role-permissions', [UserAccountsController::class, 'getRolePermissions']);
    Route::post('user-accounts/{id}/deactivate', [UserAccountsController::class, 'deactivate']);
    Route::post('user-accounts/{id}/reactivate', [UserAccountsController::class, 'reactivate']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Technicians CRUD
    Route::apiResource('technicians', TechnicianController::class);

    Route::get('technician-user-data/{id}', [TechnicianController::class, 'userData']);


    // Additional technician routes
    Route::get('technicians/{id}/installations', [TechnicianController::class, 'technicianInstallations']);
    Route::post('technicians/{id}/assign-installations', [TechnicianController::class, 'assignInstallations']);
    Route::delete('technicians/{technicianId}/installations/{installationId}', [TechnicianController::class, 'removeInstallation']);
    Route::get('technicians/{id}/statistics', [TechnicianController::class, 'statistics']);
    Route::post('technicians/{id}/toggle-status', [TechnicianController::class, 'toggleStatus']);
    Route::get('available-facilities', [TechnicianController::class, 'availableFacilities']);
});


Route::middleware('auth:sanctum')->group(function () {
    // Facility-Technician Assignment Routes
    Route::prefix('assignments')->group(function () {
        // Available resources
        Route::get('available-facilities', [FacilityTechnicianAssignmentController::class, 'availableFacilities']);
        Route::get('available-technicians', [FacilityTechnicianAssignmentController::class, 'availableTechnicians']);

        // Assignment operations
        Route::post('assign', [FacilityTechnicianAssignmentController::class, 'assignTechnician']);
        Route::post('batch-assign', [FacilityTechnicianAssignmentController::class, 'batchAssign']);
        Route::put('reassign/{installationId}', [FacilityTechnicianAssignmentController::class, 'reassignTechnician']);
        Route::delete('remove/{installationId}', [FacilityTechnicianAssignmentController::class, 'removeAssignment']);

        // Reports and statistics
        Route::get('statistics', [FacilityTechnicianAssignmentController::class, 'assignmentStatistics']);
        Route::get('technician-workload', [FacilityTechnicianAssignmentController::class, 'technicianWorkload']);
    });
});

Route::apiResource('lgas', LgaController::class);
Route::apiResource('states', StateController::class);

Route::apiResource('installation-assignments', InstallationAssignmentController::class);

// Bulk assignment routes
Route::post('installation-assignments/bulk-assign', [InstallationAssignmentController::class, 'bulkAssign']);
Route::post('installation-assignments/bulk-assign-atomic', [InstallationAssignmentController::class, 'bulkAssignAtomic']);

