<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LgaController;

use App\Http\Controllers\StateController;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\InstallationController;
use App\Http\Controllers\LccoPrController;
use App\Http\Controllers\UserAccountsController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\InstallationAssignmentController;
use App\Http\Controllers\FacilityTechnicianAssignmentController;

use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ChecklistSectionController;
use App\Http\Controllers\ChecklistQuestionController;
use App\Http\Controllers\InstallationChecklistController;
use App\Http\Controllers\InstallationChecklistAnswerController;
use App\Http\Controllers\InstallationChecklistDraftController;

use App\Http\Controllers\ImageController;





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

// Add these routes
Route::patch('/installations/{id}/delivery-status', [InstallationController::class, 'updateDeliveryStatus']);
Route::patch('/installations/{id}/installation-status', [InstallationController::class, 'updateInstallationStatus']);
Route::put('/installations/{id}', [InstallationController::class, 'update']);


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

Route::get('/installations/export/all', [InstallationController::class, 'exportAllInstallations']);

/*
|--------------------------------------------------------------------------
| API Routes for Checklist System
|--------------------------------------------------------------------------
*/

// Checklist management routes
Route::apiResource('checklists', ChecklistController::class);

// Get active checklist
Route::get('checklists/active/current', [ChecklistController::class, 'getActiveChecklist']);

// Checklist sections routes
Route::prefix('checklists/{checklistId}')->group(function () {
    Route::apiResource('sections', ChecklistSectionController::class);

    // Reorder sections
    Route::post('sections/reorder', [ChecklistSectionController::class, 'reorder']);
});

// Checklist questions routes
Route::prefix('checklists/{checklistId}/sections/{sectionId}')->group(function () {
    Route::apiResource('questions', ChecklistQuestionController::class);

    // Reorder questions
    Route::post('questions/reorder', [ChecklistQuestionController::class, 'reorder']);

    // Get validation rules for a question
    Route::get('questions/{questionId}/validation-rules', [ChecklistQuestionController::class, 'getValidationRules']);
});

// Installation checklist routes
Route::prefix('installations/{installationId}')->group(function () {

    // Get checklist structure for installation
    Route::get('checklist/structure', [InstallationChecklistController::class, 'getChecklistStructure']);

    // Draft management
    Route::prefix('checklist/draft')->group(function () {
        Route::get('/', [InstallationChecklistController::class, 'getDraft']);
        Route::post('/', [InstallationChecklistController::class, 'saveDraft']);
        Route::delete('/', [InstallationChecklistController::class, 'deleteDraft']);
    });

    // Submit checklist
    Route::post('checklist/submit', [InstallationChecklistController::class, 'submitChecklist']);

    // Get submitted checklists for installation
    Route::get('checklists', [InstallationChecklistController::class, 'getInstallationChecklists']);

    // Installation checklist answers
    Route::prefix('checklists/{installationChecklistId}/answers')->group(function () {
        Route::get('/', [InstallationChecklistAnswerController::class, 'index']);
        Route::post('/', [InstallationChecklistAnswerController::class, 'store']);
        Route::post('bulk', [InstallationChecklistAnswerController::class, 'bulkUpdate']);
        Route::get('by-section', [InstallationChecklistAnswerController::class, 'getBySection']);
        Route::get('question/{questionCode}', [InstallationChecklistAnswerController::class, 'getByQuestionCode']);
    });

    // Installation checklist drafts (specific routes)
    Route::prefix('drafts')->group(function () {
        Route::get('/', [InstallationChecklistDraftController::class, 'index']);
        Route::get('{draftId}', [InstallationChecklistDraftController::class, 'show']);
        Route::delete('{draftId}', [InstallationChecklistDraftController::class, 'destroy']);
    });
});

// Global draft management
Route::prefix('drafts')->group(function () {
    Route::get('stats', [InstallationChecklistDraftController::class, 'getStats']);
    Route::post('cleanup', [InstallationChecklistDraftController::class, 'cleanupStaleDrafts']);
});

// Installation checklist answers direct routes
Route::prefix('installation-checklists/{installationChecklistId}/answers')->group(function () {
    Route::apiResource('/', InstallationChecklistAnswerController::class)->except(['store']);
    Route::get('{answerId}', [InstallationChecklistAnswerController::class, 'show']);
    Route::put('{answerId}', [InstallationChecklistAnswerController::class, 'update']);
    Route::delete('{answerId}', [InstallationChecklistAnswerController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('installations/{installation}')->group(function () {
        Route::get('/images', [ImageController::class, 'index']);
        Route::post('/images', [ImageController::class, 'store']);
        Route::get('/images/{image}', [ImageController::class, 'show']);
        Route::get('/images/{image}/original', [ImageController::class, 'getOriginal']);
        Route::get('/images/{image}/watermarked', [ImageController::class, 'getWatermarked']);
        Route::delete('/images/{image}', [ImageController::class, 'destroy']);
        Route::get('/images/{image}/download', [ImageController::class, 'download']);
    });
});


Route::get('/watermark/status', [ImageController::class, 'watermarkStatus']);

// LccoPr routes (protected)

    Route::get('/lcco-pr', [LccoPrController::class, 'index']);
    Route::post('/lcco-pr', [LccoPrController::class, 'store']);
    Route::get('/lcco-pr/{id}', [LccoPrController::class, 'show']);
    Route::put('/lcco-pr/{id}', [LccoPrController::class, 'update']);

