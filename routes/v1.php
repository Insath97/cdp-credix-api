<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\ActivityController;
use App\Http\Controllers\V1\PermissionController;
use App\Http\Controllers\V1\RoleController;
use App\Http\Controllers\V1\UserController;
use App\Http\Controllers\V1\DepartmentController;
use App\Http\Controllers\V1\ProvinceController;
use App\Http\Controllers\V1\RegionController;
use App\Http\Controllers\V1\ZonalController;
use App\Http\Controllers\V1\BranchController;
use App\Http\Controllers\V1\DesignationController;
use App\Http\Controllers\V1\CountryController;
use App\Http\Controllers\V1\GroupController;
use App\Http\Controllers\V1\CustomerController;
use App\Http\Controllers\V1\CustomerBankDetailController;
use App\Http\Controllers\V1\GuarantorController;
use App\Http\Controllers\V1\ApplicationController;
use App\Http\Controllers\V1\ApplicationHistoryController;
use App\Http\Controllers\V1\FixedAssestsController;
use App\Http\Controllers\V1\MovingAssestsController;
use App\Http\Controllers\V1\DocumentController;
use App\Http\Controllers\V1\LoanProductController;
use App\Http\Controllers\V1\LoanApplicationController;
use App\Http\Controllers\V1\LoanApplicationGuarantorController;
use Illuminate\Support\Facades\Route;

/* public routes */

Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

/* protected routes */
Route::middleware(['auth:api'])->prefix('v1')->group(function () {

    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::apiResource('activities', ActivityController::class);

    Route::get('permissions/list', [PermissionController::class, 'getPermissionList']);
    Route::apiResource('permissions', PermissionController::class);

    Route::get('roles/list/', [RoleController::class, 'getAvailableRoles']);
    Route::apiResource('roles', RoleController::class);

    Route::patch('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::apiResource('users', UserController::class);

    // Countries
    Route::prefix('countries')->group(function () {
        Route::get('list', [CountryController::class, 'getActiveList']);
        Route::patch('{id}/toggle-status', [CountryController::class, 'toggleStatus']);
    });
    Route::apiResource('countries', CountryController::class);

    // Provinces
    Route::prefix('provinces')->group(function () {
        Route::get('list', [ProvinceController::class, 'getProvinceList']);
        Route::patch('{id}/toggle-status', [ProvinceController::class, 'toggleStatus']);
    });
    Route::apiResource('provinces', ProvinceController::class);

    // Zonals (Zones)
    Route::prefix('zonals')->group(function () {
        Route::get('list', [ZonalController::class, 'getZonalList']);
        Route::patch('{id}/toggle-status', [ZonalController::class, 'toggleStatus']);
    });
    Route::apiResource('zonals', ZonalController::class);

    // Regions
    Route::prefix('regions')->group(function () {
        Route::get('list', [RegionController::class, 'getRegionList']);
        Route::patch('{id}/toggle-status', [RegionController::class, 'toggleStatus']);
    });
    Route::apiResource('regions', RegionController::class);

    // Branches
    Route::prefix('branches')->group(function () {
        Route::get('list', [BranchController::class, 'getBranchList']);
        Route::patch('{id}/toggle-status', [BranchController::class, 'toggleStatus']);
    });
    Route::apiResource('branches', BranchController::class);

    // Departments
    Route::prefix('departments')->group(function () {
        Route::get('{id}/designations', [DepartmentController::class, 'getDesignations']);
        Route::patch('{id}/toggle-status', [DepartmentController::class, 'toggleStatus']);
    });
    Route::apiResource('departments', DepartmentController::class);

    // Designations
    Route::prefix('designations')->group(function () {
        Route::get('list', [DesignationController::class, 'getActiveList']);
        Route::patch('{id}/toggle-status', [DesignationController::class, 'toggleStatus']);
    });
    Route::apiResource('designations', DesignationController::class);

    // Groups
    Route::prefix('groups')->group(function () {
        Route::get('list', [GroupController::class, 'getActiveList']);
        Route::patch('{id}/toggle-status', [GroupController::class, 'toggleStatus']);
    });
    Route::apiResource('groups', GroupController::class);

    // Customers
    Route::apiResource('customers', CustomerController::class);
    Route::prefix('customers')->group(function () {
        Route::patch('{id}/toggle-status', [CustomerController::class, 'toggleStatus']);
        Route::post('{id}/restore', [CustomerController::class, 'restore']);
        Route::delete('{id}/force-delete', [CustomerController::class, 'forceDelete']);
        Route::get('list/public/{customer_code?}', [CustomerController::class, 'getPublicDetails']);
    });

    // Customer Bank Details
    Route::apiResource('customer-bank-details', CustomerBankDetailController::class);
    Route::prefix('customer-bank-details')->group(function () {
        Route::patch('{id}/toggle-status', [CustomerBankDetailController::class, 'toggleStatus']);
    });

    // Guarantors
    Route::apiResource('guarantors', GuarantorController::class);

    // Applications
    Route::apiResource('applications', ApplicationController::class);

    // Application Histories
    Route::apiResource('application-histories', ApplicationHistoryController::class);

    // Fixed Assets
    Route::apiResource('fixed-assests', FixedAssestsController::class);

    // Moving Assets
    Route::apiResource('moving-assests', MovingAssestsController::class);

    // Documents
    Route::apiResource('documents', DocumentController::class);

    // Loan Products
    Route::prefix('loan-products')->group(function () {
        Route::get('list', [LoanProductController::class, 'getActiveList']);
        Route::patch('{id}/toggle-status', [LoanProductController::class, 'toggleStatus']);
        Route::patch('{id}/activate', [LoanProductController::class, 'activate']);
        Route::patch('{id}/deactivate', [LoanProductController::class, 'deactivate']);
    });
    Route::apiResource('loan-products', LoanProductController::class);

    // Loan Applications
    Route::prefix('loan-applications')->group(function () {
        Route::patch('{id}/toggle-status', [LoanApplicationController::class, 'toggleStatus']);
        Route::patch('{id}/activate', [LoanApplicationController::class, 'activate']);
        Route::patch('{id}/deactivate', [LoanApplicationController::class, 'deactivate']);
        Route::patch('{id}/submit', [LoanApplicationController::class, 'submit']);
        Route::patch('{id}/review', [LoanApplicationController::class, 'review']);
        Route::patch('{id}/approve', [LoanApplicationController::class, 'approve']);
        Route::patch('{id}/reject', [LoanApplicationController::class, 'reject']);
        Route::patch('{id}/disburse', [LoanApplicationController::class, 'disburse']);
        Route::patch('{id}/cancel', [LoanApplicationController::class, 'cancel']);
    });
    Route::apiResource('loan-applications', LoanApplicationController::class);

    // Loan Application Guarantors
    Route::apiResource('loan-application-guarantors', LoanApplicationGuarantorController::class);

});
