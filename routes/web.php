<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AddressLookupController;
use App\Http\Controllers\AdminOperationsController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\StudentEmailController;
use App\Http\Controllers\Auth\TemporaryPasswordController;
use App\Http\Controllers\CertificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiplomaController;
use App\Http\Controllers\DocumentVerificationController;
use App\Http\Controllers\Form137Controller;
use App\Http\Controllers\Form138Controller;
use App\Http\Controllers\GeneratorController;
use App\Http\Controllers\GoodMoralController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\MessageHubController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RequestTypeController;
use App\Http\Controllers\SchoolFormController;
use App\Http\Controllers\SchoolFormF137Controller;
use App\Http\Controllers\SchoolFormF138Controller;
use App\Http\Controllers\SchoolFormRecordController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentMasterlistController;
use App\Http\Controllers\StudentNotificationController;
use App\Http\Controllers\UserController;
use App\Support\SystemContent;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::get('/school-logo', fn () => response()->file(SystemContent::logoPath(), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-cache']))->name('school.logo');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
Route::get('/staff/login', [LoginController::class, 'showStaffLoginForm'])->name('login.staff');
Route::post('/staff/login', [LoginController::class, 'loginStaff'])->middleware('throttle:login')->name('login.staff.submit');
Route::get('/admin/login', [LoginController::class, 'showAdminLoginForm'])->name('login.admin');
Route::post('/admin/login', [LoginController::class, 'loginAdmin'])->middleware('throttle:login')->name('login.admin.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('guest')->group(function () {
    Route::get('/create-student-account', fn () => redirect()->route('login')->with('status', 'Student accounts are created by the school. Ask the Registrar for your random temporary password.'))
        ->name('student.registration.request');

    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/account/change-temporary-password', [TemporaryPasswordController::class, 'edit'])->name('temporary-password.edit');
    Route::post('/account/change-temporary-password', [TemporaryPasswordController::class, 'update'])->middleware('throttle:5,1')->name('temporary-password.update');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::post('/email/verify/skip', [EmailVerificationController::class, 'skip'])
        ->name('verification.skip');
});

// Public, signed document verification endpoints used by printed QR codes.
Route::get('/verify', [DocumentVerificationController::class, 'lookup'])->name('documents.lookup');
Route::get('/verify/{identifier}', [DocumentVerificationController::class, 'show'])
    ->where('identifier', '[A-Za-z0-9-]+')
    ->name('documents.verify');
Route::post('/verify/{identifier}/file', [DocumentVerificationController::class, 'verifyFile'])
    ->where('identifier', '[A-Za-z0-9-]+')
    ->name('documents.verify-file');
Route::post('/verify/{identifier}/photo-check', [DocumentVerificationController::class, 'verifyPhotoText'])
    ->middleware('throttle:10,1')
    ->where('identifier', '[A-Za-z0-9-]+')
    ->name('documents.verify-photo');

Route::middleware(['auth', 'student.verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::middleware('role:student,registrar')->group(function () {
        Route::get('/messages', [MessageHubController::class, 'index'])->name('messages.index');
        Route::post('/messages', [MessageHubController::class, 'store'])->middleware('role:student')->name('messages.store');
        Route::post('/messages/{thread}/reply', [MessageHubController::class, 'reply'])->name('messages.reply');
    });
    Route::get('/requests/{requestDocument}/receipt', [RequestController::class, 'receipt'])
        ->middleware('throttle:60,1')
        ->name('requests.receipt');
    Route::get('/requests/{requestDocument}/accounting-receipt', [RequestController::class, 'uploadedReceipt'])
        ->middleware('throttle:60,1')
        ->name('requests.receipt.uploaded');
    Route::get('/grade-portal/uploads/{upload}', [GradeController::class, 'view'])
        ->middleware('throttle:60,1')
        ->name('grade-portal.uploads.view');

    // User Management and student password assistance
    Route::middleware('role:admin,registrar')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/issue-temporary-password', [UserController::class, 'issueTemporaryPassword'])->name('users.issue-temporary-password');
    });
    Route::middleware('role:admin')->group(function () {
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/demo-student/calvin-james-manalo', [UserController::class, 'createDemoStudent'])->name('users.demo-student');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/archive/{id}/restore', [UserController::class, 'restore'])->whereNumber('id')->name('users.restore');
        Route::delete('/users/archive/{id}', [UserController::class, 'forceDestroy'])->whereNumber('id')->name('users.force-destroy');
        Route::post('/users/{user}/toggle-bypass', [UserController::class, 'toggleBypass'])->name('users.toggle-bypass');
    });

    Route::middleware('role:student')->group(function () {
        Route::get('/account/email', [StudentEmailController::class, 'edit'])->name('student.email.edit');
        Route::post('/account/email', [StudentEmailController::class, 'requestChange'])
            ->middleware('throttle:3,10')
            ->name('student.email.request');
        Route::get('/account/email/verify/{user}/{hash}', [StudentEmailController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('student.email.verify');
    });

    // Student Requests
    Route::get('/requests/{requestDocument}/attachments/{field}', [RequestController::class, 'dynamicAttachment'])->name('requests.dynamic-attachment');
    Route::middleware('role:student')->group(function () {
        Route::get('/student/address-lookup/provinces', [AddressLookupController::class, 'provinces'])->middleware('throttle:30,1')->name('student.address.provinces');
        Route::get('/student/address-lookup/cities', [AddressLookupController::class, 'cities'])->middleware('throttle:60,1')->name('student.address.cities');
        Route::get('/student/address-lookup/barangays', [AddressLookupController::class, 'barangays'])->middleware('throttle:60,1')->name('student.address.barangays');
        Route::get('/student/request', [RequestController::class, 'studentIndex'])->name('student.request');
        Route::get('/student/my-requests', [RequestController::class, 'myRequests'])->name('student.my-requests');
        Route::post('/student/request/store', [RequestController::class, 'store'])->name('student.request.store');
        Route::get('/student/notifications', [StudentNotificationController::class, 'index'])->name('student.notifications.index');
        Route::post('/student/notifications/read-all', [StudentNotificationController::class, 'markAllRead'])->name('student.notifications.read-all');
        Route::post('/student/notifications/{notification}/read', [StudentNotificationController::class, 'markRead'])->name('student.notifications.read');
    });

    // Records Officer / Registrar Request Management
    Route::middleware('role:admin,registrar,records_officer')->group(function () {
        Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/history', [RequestController::class, 'history'])->name('requests.history');
        Route::get('/requests/{requestDocument}/document-preview', [RequestController::class, 'documentQuickView'])->name('requests.document-preview');
        Route::get('/requests/{requestDocument}/document-download', [RequestController::class, 'documentDownload'])->name('requests.document-download');
        Route::get('/requests/{requestDocument}/employment-proof', [RequestController::class, 'employmentProof'])->name('requests.employment-proof');
        Route::get('/requests/{requestDocument}/request-proof', [RequestController::class, 'requestProof'])->name('requests.request-proof');
        Route::post('/requests/{request_id}/update-status', [RequestController::class, 'updateStatus'])->name('requests.update-status');
        Route::post('/requests/{request_id}/confirm-payment', [RequestController::class, 'confirmPayment'])->name('requests.confirm-payment');
        Route::post('/requests/{request_id}/assess-f137-delivery', [RequestController::class, 'assessF137Delivery'])->name('requests.assess-f137-delivery');
    });
    Route::post('/requests/{request_id}/update-clearance', [RequestController::class, 'updateClearance'])
        ->middleware('role:admin,registrar')
        ->name('requests.update-clearance');
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/account-creation', [StudentMasterlistController::class, 'index'])->name('account-creation.index');
        Route::get('/admin/account-creation/accounts', [StudentMasterlistController::class, 'accounts'])->name('account-creation.accounts');
        Route::post('/admin/account-creation/upload', [StudentMasterlistController::class, 'store'])->name('account-creation.store');
        Route::post('/admin/account-creation/{student}/reissue-password', [StudentMasterlistController::class, 'reissueTemporaryPassword'])->name('account-creation.reissue-password');
        Route::delete('/admin/account-creation/{student}/account', [StudentMasterlistController::class, 'destroyGeneratedAccount'])->name('account-creation.destroy-account');
        Route::delete('/admin/account-creation-data', [StudentMasterlistController::class, 'purgeAccountCreationData'])->name('account-creation.purge');
        Route::delete('/requests/history/clear', [RequestController::class, 'clearHistory'])->name('requests.history.clear');
        Route::delete('/requests/reset-all', [RequestController::class, 'resetAll'])->name('requests.reset-all');
    });

    // Integrated F137/F138 school-forms module
    Route::middleware('role:admin,records_officer')->group(function () {
        Route::get('/school-forms', [SchoolFormController::class, 'home'])->name('school-forms.home');
        Route::get('/school-forms/request/{documentRequest?}', [GeneratorController::class, 'maker'])->name('generator.maker');
        Route::get('/school-forms/f137/preview', [SchoolFormF137Controller::class, 'preview'])->name('school-forms.f137.preview');
        Route::get('/school-forms/f137/pdf', [SchoolFormF137Controller::class, 'pdf'])->name('school-forms.f137.pdf');
        Route::get('/school-forms/f137/download', [SchoolFormF137Controller::class, 'download'])->name('school-forms.f137.download');
        Route::get('/school-forms/f137/excel-preview', [SchoolFormF137Controller::class, 'excelPreview'])->name('school-forms.f137.excel-preview');
        Route::get('/school-forms/f137/template', [SchoolFormF137Controller::class, 'template'])->name('school-forms.f137.template');
        Route::get('/school-forms/f138/preview', [SchoolFormF138Controller::class, 'preview'])->name('school-forms.f138.preview');
        Route::get('/school-forms/f138/pdf', [SchoolFormF138Controller::class, 'pdf'])->name('school-forms.f138.pdf');
        Route::get('/school-forms/f138/download', [SchoolFormF138Controller::class, 'download'])->name('school-forms.f138.download');
        Route::post('/school-forms/f138/finalize', [SchoolFormF138Controller::class, 'finalize'])->name('school-forms.f138.finalize');
    });
    Route::middleware('role:admin,registrar')->group(function () {
        Route::get('/school-forms/grade-sheets', [GeneratorController::class, 'gradeSheets'])->name('generator.grade-sheets');
        Route::post('/school-forms/grade-sheets', [SchoolFormF138Controller::class, 'storeGradeSheets'])->name('school-forms.grade-sheets.store');
        Route::post('/school-forms/grade-sheets/auto-batch', [SchoolFormF138Controller::class, 'storeAutoBatch'])->name('school-forms.grade-sheets.auto-batch');
        Route::get('/school-forms/grade-sheets/status', [SchoolFormRecordController::class, 'status'])->name('school-forms.grade-sheets.status');
        Route::get('/school-forms/grade-sheets/template/{type}', [SchoolFormRecordController::class, 'template'])->name('school-forms.grade-sheets.template');
        Route::get('/grade-portal/class-sheets', [SchoolFormRecordController::class, 'index'])->name('school-forms.records');
        Route::get('/school-forms/records', fn () => redirect()->route('school-forms.records', request()->query()));
        Route::get('/school-forms/uploads/{upload}', [SchoolFormRecordController::class, 'download'])->name('school-forms.uploads.download');
        Route::get('/school-forms/uploads/{upload}/preview', [SchoolFormRecordController::class, 'preview'])->name('school-forms.uploads.preview');
        Route::delete('/school-forms/grade-sheets/school-year', [SchoolFormRecordController::class, 'destroySchoolYear'])->name('school-forms.grade-sheets.destroy-school-year');
    });
    Route::delete('/school-forms/uploads/{upload}', [SchoolFormRecordController::class, 'destroy'])
        ->middleware('role:admin,registrar')
        ->name('school-forms.uploads.destroy');

    // The Grade Portal now opens the class-sheet workspace and its student-grade finder.
    Route::middleware('role:admin,registrar')->group(function () {
        Route::get('/grade-portal', [SchoolFormRecordController::class, 'index'])->name('grade-portal.index');
        Route::post('/grade-portal/upload', [GradeController::class, 'upload'])->name('grade-portal.upload');
        Route::delete('/grade-portal/delete/{id}', [GradeController::class, 'deleteUpload'])->name('grade-portal.delete');
    });

    // Form 137 Maker (Records Officer)
    Route::middleware('role:admin')->group(function () {
        Route::get('/form-137', [Form137Controller::class, 'index'])->name('form-137.index');
        Route::post('/form-137/preview-manual', [Form137Controller::class, 'previewManual'])->name('form-137.preview-manual');
        Route::post('/form-137/generate-manual', [Form137Controller::class, 'generateManual'])->name('form-137.generate-manual');
        Route::get('/form-137/download/{student_number}', [Form137Controller::class, 'downloadExcel'])->name('form-137.download');
        Route::get('/form-137/view-html/{id}', [Form137Controller::class, 'viewHtml'])->name('form-137.view-html');

        // Form 138 Maker
        Route::get('/form-138', [Form138Controller::class, 'index'])->name('form-138.index');
        Route::post('/form-138/generate', [Form138Controller::class, 'generate'])->name('form-138.generate');
    });

    // Certification Maker
    Route::middleware('role:admin,registrar,records_officer')->group(function () {
        Route::get('/certifications', [CertificationController::class, 'index'])->name('certifications.index');
        Route::post('/certifications/preview', [CertificationController::class, 'preview'])->name('certifications.preview');
        Route::post('/certifications/pdf', [CertificationController::class, 'pdf'])->name('certifications.pdf');
        Route::post('/certifications/forward', [CertificationController::class, 'forward'])->name('certifications.forward');
    });
    Route::post('/certifications/finalize', [CertificationController::class, 'finalize'])
        ->middleware('role:admin,records_officer')
        ->name('certifications.finalize');

    // Good Moral Maker
    Route::middleware('role:admin,registrar,records_officer')->group(function () {
        Route::get('/good-moral', [GoodMoralController::class, 'index'])->name('good-moral.index');
        Route::get('/good-moral/preview/{id}', [GoodMoralController::class, 'previewFromRequest'])->name('good-moral.preview-request');
        Route::post('/good-moral/generate', [GoodMoralController::class, 'generate'])->name('good-moral.generate');
        Route::post('/good-moral/submit', [GoodMoralController::class, 'submitToRegistrar'])->name('good-moral.submit');

        // Diploma Maker
        Route::get('/diploma', [DiplomaController::class, 'index'])->name('diploma.index');
        Route::post('/diploma/submit', [DiplomaController::class, 'submitToRegistrar'])->name('diploma.submit');
    });

    // System & Settings
    Route::middleware('role:admin')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
        Route::delete('/logs/clear', [ActivityLogController::class, 'clear'])->name('logs.clear');
    });
    Route::middleware('role:registrar')->group(function () {
        Route::patch('/request-types/built-in/{key}/price', [RequestTypeController::class, 'updateBuiltInPrice'])->name('request-types.built-in.price');
        Route::patch('/request-types/built-in/{key}/availability', [RequestTypeController::class, 'setBuiltInAvailability'])->name('request-types.built-in.availability');
        Route::patch('/request-types/{requestType}/availability', [RequestTypeController::class, 'toggle'])->name('request-types.toggle');
        Route::resource('request-types', RequestTypeController::class)->except(['show', 'create', 'edit']);
        Route::post('/settings/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::patch('/settings/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::patch('/settings/announcements/{announcement}/archive', [AnnouncementController::class, 'archive'])->name('announcements.archive');
    });
    Route::middleware('role:admin,registrar')->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    });
    Route::middleware('role:admin')->group(function () {
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/backup', [AdminOperationsController::class, 'backup'])->name('settings.backup');
        Route::get('/settings/backup/{file}', [AdminOperationsController::class, 'downloadBackup'])->name('settings.backup.download');
        Route::post('/settings/test-email', [AdminOperationsController::class, 'testEmail'])->name('settings.test-email');
    });
    Route::post('/documents/authenticity/{document}/revoke', [DocumentVerificationController::class, 'revoke'])
        ->middleware('role:admin,registrar,records_officer')
        ->name('documents.revoke');
    Route::get('/documents/authenticity/{document}/issued', [DocumentVerificationController::class, 'issued'])
        ->name('documents.issued');
    Route::get('/documents/authenticity/{document}/download', [DocumentVerificationController::class, 'downloadOfficial'])
        ->name('documents.download');
});
