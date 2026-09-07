<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\ActivityController;
use App\Http\Controllers\V1\PermissionController;
use App\Http\Controllers\V1\RoleController;
use App\Http\Controllers\V1\UserController;
use App\Http\Controllers\V1\EmployeeController;
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
use App\Http\Controllers\V1\LiabilityController;
use App\Http\Controllers\V1\ApplicationController;
use App\Http\Controllers\V1\ApplicationHistoryController;
use App\Http\Controllers\V1\FixedAssestsController;
use App\Http\Controllers\V1\MovingAssestsController;
use App\Http\Controllers\V1\DocumentController;
use App\Http\Controllers\V1\LoanTermController;
use App\Http\Controllers\V1\LoanTypeController;
use App\Http\Controllers\V1\LoanProductController;
use App\Http\Controllers\V1\LoanApplicationController;
use App\Http\Controllers\V1\GroupLoanController;
use App\Http\Controllers\V1\GroupLoanItemController;
use App\Http\Controllers\V1\LoanApplicationGuarantorController;
use App\Http\Controllers\V1\LoanApplicationCustomerController;
use App\Http\Controllers\V1\LoanApplicationFixedAssetController;
use App\Http\Controllers\V1\LoanApplicationMovingAssetController;
use App\Http\Controllers\V1\LoanApplicationLiabilityController;
use App\Http\Controllers\V1\LoanApplicationBankDetailController;
use App\Http\Controllers\V1\LoanInstallmentController;
use App\Http\Controllers\V1\LoanRevisionController;
use App\Http\Controllers\V1\PaymentController;
use App\Http\Controllers\V1\RecoveryCaseController;
use App\Http\Controllers\V1\RecoveryActivityController;
use App\Http\Controllers\V1\RecoveryAgentController;
use App\Http\Controllers\V1\ExternalRecoveryAgentController;
use App\Http\Controllers\V1\NotificationController;
use App\Http\Controllers\V1\PasswordChangeController;
use App\Http\Controllers\V1\SettingController;
use App\Http\Controllers\V1\AdminDashboardController;
use App\Http\Controllers\V1\ReportController;
use App\Http\Controllers\V1\Customer\CustomerDashboardController;
use App\Http\Controllers\V1\Customer\CustomerProfileController;
use App\Http\Controllers\V1\Customer\CustomerLoanController;
use App\Http\Controllers\V1\Customer\CustomerPaymentController;
use App\Http\Controllers\V1\Customer\CustomerNotificationController;
use App\Http\Controllers\V1\Customer\CustomerDocumentController;
use App\Http\Controllers\V1\Customer\CustomerFinancialProfileController;
use Illuminate\Support\Facades\Route;

/* public routes */

Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('login/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:otp-verify');
    Route::post('forgot-password', [PasswordChangeController::class, 'forgotPassword'])->middleware('throttle:otp-request');
    Route::post('reset-forgot-password', [PasswordChangeController::class, 'resetForgotPassword'])->middleware('throttle:otp-verify');
});

/* protected routes */
Route::middleware(['auth:api'])->prefix('v1')->group(function () {

    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    // Password
    Route::prefix('password')->group(function () {
        Route::post('request-change', [PasswordChangeController::class, 'requestChange'])->middleware('throttle:otp-request');
        Route::post('change-with-otp', [PasswordChangeController::class, 'changeWithOtp'])->middleware('throttle:otp-verify');
    });

    /*ActivityLog*/
    Route::apiResource('activities', ActivityController::class);

    /*Permissions*/
    Route::get('permissions/list', [PermissionController::class, 'getPermissionList']);
    Route::apiResource('permissions', PermissionController::class);

    /*Roles*/
    Route::get('roles/list/', [RoleController::class, 'getAvailableRoles']);
    Route::apiResource('roles', RoleController::class);

    /*Users*/
    Route::patch('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::apiResource('users', UserController::class);

    // Employees
    Route::prefix('employees')->group(function () {
        Route::get('list', [EmployeeController::class, 'getActiveList']);
    });

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
        Route::get('list', [DepartmentController::class, 'getDepartmentList']);
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
        Route::get('list', [CustomerController::class, 'index']);
        Route::get('list/public/{customer_code?}', [CustomerController::class, 'getPublicDetails']);
        Route::patch('{id}/toggle-status', [CustomerController::class, 'toggleStatus']);
        Route::post('{id}/restore', [CustomerController::class, 'restore']);
        Route::delete('{id}/force-delete', [CustomerController::class, 'forceDelete']);
    });

    // Customer Bank Details
    Route::prefix('customer-bank-details')->group(function () {
        Route::get('bank-options', [CustomerBankDetailController::class, 'bankOptions']);
        Route::patch('{id}/toggle-status', [CustomerBankDetailController::class, 'toggleStatus']);
    });
    Route::apiResource('customer-bank-details', CustomerBankDetailController::class);

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

    // Liabilities
    Route::apiResource('liabilities', LiabilityController::class);
    Route::prefix('liabilities')->group(function () {
        Route::patch('{id}/toggle-status', [LiabilityController::class, 'toggleStatus']);
    });

    // Documents
    Route::apiResource('documents', DocumentController::class);

    // Loan Terms
    Route::prefix('loan-terms')->group(function () {
        Route::get('list', [LoanTermController::class, 'getActiveList']);
        Route::patch('{id}/toggle-status', [LoanTermController::class, 'toggleStatus']);
    });
    Route::apiResource('loan-terms', LoanTermController::class);

    // Loan Types
    Route::prefix('loan-types')->group(function () {
        Route::get('list', [LoanTypeController::class, 'getActiveList']);
        Route::patch('{id}/toggle-status', [LoanTypeController::class, 'toggleStatus']);
    });
    Route::apiResource('loan-types', LoanTypeController::class);

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
        Route::patch('{id}/review', [LoanApplicationController::class, 'review']);
        Route::patch('{id}/verify', [LoanApplicationController::class, 'verify']);
        Route::patch('{id}/approve', [LoanApplicationController::class, 'approve']);
        Route::patch('{id}/reject', [LoanApplicationController::class, 'reject']);
        Route::patch('{id}/disburse', [LoanApplicationController::class, 'disburse']);
        Route::patch('{id}/cancel', [LoanApplicationController::class, 'cancel']);
    });
    Route::apiResource('loan-applications', LoanApplicationController::class);

    // Group Loans
    Route::prefix('group-loans')->group(function () {
        Route::get('status-counts', [GroupLoanController::class, 'statusCounts']);
        Route::get('status/{status}', [GroupLoanController::class, 'byStatus']);
        Route::patch('{id}/toggle-status', [GroupLoanController::class, 'toggleStatus']);
        Route::patch('{id}/activate', [GroupLoanController::class, 'activate']);
        Route::patch('{id}/deactivate', [GroupLoanController::class, 'deactivate']);
        Route::patch('{id}/review', [GroupLoanController::class, 'review']);
        Route::patch('{id}/verify', [GroupLoanController::class, 'verify']);
        Route::patch('{id}/approve', [GroupLoanController::class, 'approve']);
        Route::patch('{id}/reject', [GroupLoanController::class, 'reject']);
        Route::patch('{id}/disburse', [GroupLoanController::class, 'disburse']);
        Route::patch('{id}/cancel', [GroupLoanController::class, 'cancel']);
    });
    Route::apiResource('group-loans', GroupLoanController::class);

    // Group Loan Items
    Route::apiResource('group-loan-items', GroupLoanItemController::class)->only(['index', 'store', 'show', 'destroy']);

    // Loan Application Guarantors
    Route::apiResource('loan-application-guarantors', LoanApplicationGuarantorController::class);

    // Loan Application Customers — Joint Loan co-borrowers and Group Loan
    // members alike. Group members are only editable while the group loan is
    // Available; the controller enforces that.
    Route::patch('loan-application-customers/{id}/customer-details', [LoanApplicationCustomerController::class, 'updateCustomerDetails']);
    Route::apiResource('loan-application-customers', LoanApplicationCustomerController::class)
        ->only(['index', 'store', 'show', 'destroy'])
        ->parameters(['loan-application-customers' => 'id']);

    // Loan Application Fixed Assets (pledged assets)
    Route::apiResource('loan-application-fixed-assets', LoanApplicationFixedAssetController::class)->only(['index', 'store', 'show', 'destroy']);

    // Loan Application Moving Assets (pledged assets)
    Route::apiResource('loan-application-moving-assets', LoanApplicationMovingAssetController::class)->only(['index', 'store', 'show', 'destroy']);

    // Loan Application Liabilities (linked liabilities)
    Route::apiResource('loan-application-liabilities', LoanApplicationLiabilityController::class)->only(['index', 'store', 'show', 'destroy']);

    // Loan Application Bank Details (linked bank details)
    Route::apiResource('loan-application-bank-details', LoanApplicationBankDetailController::class)->only(['index', 'store', 'show', 'destroy']);

    // Loan Installments
    Route::prefix('loan-installments')->group(function () {
        Route::get('list', [LoanInstallmentController::class, 'list']);
    });
    Route::apiResource('loan-installments', LoanInstallmentController::class);

    // Loan Revisions(islamic)
    Route::prefix('loan-revisions')->group(function () {
        Route::patch('{id}/approve', [LoanRevisionController::class, 'approve']);
        Route::patch('{id}/reject', [LoanRevisionController::class, 'reject']);
        Route::patch('{id}/cancel', [LoanRevisionController::class, 'cancel']);
        Route::get('{id}/document', [LoanRevisionController::class, 'downloadDocument']);
    });
    Route::apiResource('loan-revisions', LoanRevisionController::class)->only(['index', 'store', 'show']);

    // Payments
    Route::apiResource('payments', PaymentController::class);

    // Recovery Cases
    Route::patch('recovery-cases/{id}/assign-agent', [RecoveryCaseController::class, 'assignAgent']);
    Route::apiResource('recovery-cases', RecoveryCaseController::class);

    // Recovery Activities
    Route::apiResource('recovery-activities', RecoveryActivityController::class);

    // Recovery Agents combined Externl Agents
    Route::prefix('recovery-agents')->group(function () {
        Route::get('combined', [RecoveryAgentController::class, 'combinedList']);
    });
    Route::apiResource('recovery-agents', RecoveryAgentController::class);

    // External Recovery Agents
    Route::apiResource('external-recovery-agents', ExternalRecoveryAgentController::class);

    // Notifications (read-only audit log)
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'show']);

    // System Settings
    Route::prefix('settings')->group(function () {
        Route::get('list', [SettingController::class, 'list']);
    });
    Route::get('settings', [SettingController::class, 'index']);
    Route::get('settings/{key}', [SettingController::class, 'show']);
    Route::put('settings/{key}', [SettingController::class, 'update']);

    // Admin Dashboard
    Route::prefix('admin-dashboard')->group(function () {
        Route::get('overview', [AdminDashboardController::class, 'overview']);
        Route::get('recent-transactions', [AdminDashboardController::class, 'recentTransactions']);
        Route::get('recent-loan-applications', [AdminDashboardController::class, 'recentLoanApplications']);

        Route::get('targets', [AdminDashboardController::class, 'targetIndex']);
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('branch-wise', [ReportController::class, 'branchWise']);
        Route::get('customer-wise', [ReportController::class, 'customerWise']);
        Route::get('loan-portfolio', [ReportController::class, 'loanPortfolio']);

        Route::get('recovery', [ReportController::class, 'recovery']);
        Route::get('recovery/{id}', [ReportController::class, 'recoveryShow']);
    });

});

/* customer dashboard routes */
Route::middleware(['auth:api', 'customer.auth'])->prefix('v1/my')->group(function () {
    Route::get('dashboard', [CustomerDashboardController::class, 'overview']);

    Route::get('profile', [CustomerProfileController::class, 'show']);
    Route::patch('profile', [CustomerProfileController::class, 'update']);

    Route::get('loan-applications', [CustomerLoanController::class, 'applications']);
    Route::get('loan-applications/{id}', [CustomerLoanController::class, 'applicationShow']);

    Route::get('loans', [CustomerLoanController::class, 'index']);
    Route::get('loans/{id}', [CustomerLoanController::class, 'show']);
    Route::get('loans/{id}/installments', [CustomerLoanController::class, 'installments']);
    Route::get('loans/{id}/revisions', [CustomerLoanController::class, 'revisions']);
    Route::get('loans/{id}/statement', [CustomerLoanController::class, 'statement']);

    Route::get('payments', [CustomerPaymentController::class, 'index']);
    Route::get('payments/{id}', [CustomerPaymentController::class, 'show']);

    Route::get('notifications', [CustomerNotificationController::class, 'index']);

    Route::get('documents', [CustomerDocumentController::class, 'index']);
    Route::get('documents/{id}/download', [CustomerDocumentController::class, 'download']);

    Route::get('guarantors', [CustomerFinancialProfileController::class, 'guarantors']);
    Route::get('fixed-assets', [CustomerFinancialProfileController::class, 'fixedAssets']);
    Route::get('moving-assets', [CustomerFinancialProfileController::class, 'movingAssets']);
    Route::get('liabilities', [CustomerFinancialProfileController::class, 'liabilities']);
    Route::get('bank-details', [CustomerFinancialProfileController::class, 'bankDetails']);
});
