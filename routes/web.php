<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\StudentEmailController;
use App\Http\Controllers\Auth\StudentRegistrationController;
use App\Http\Controllers\CertificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiplomaController;
use App\Http\Controllers\DocumentVerificationController;
use App\Http\Controllers\Form137Controller;
use App\Http\Controllers\Form138Controller;
use App\Http\Controllers\GeneratorController;
use App\Http\Controllers\GoodMoralController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\SchoolFormController;
use App\Http\Controllers\SchoolFormF137Controller;
use App\Http\Controllers\SchoolFormF138Controller;
use App\Http\Controllers\SchoolFormRecordController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
Route::get('/staff/login', [LoginController::class, 'showStaffLoginForm'])->name('login.staff');
Route::post('/staff/login', [LoginController::class, 'loginStaff'])->middleware('throttle:login')->name('login.staff.submit');
Route::get('/admin/login', [LoginController::class, 'showAdminLoginForm'])->name('login.admin');
Route::post('/admin/login', [LoginController::class, 'loginAdmin'])->middleware('throttle:login')->name('login.admin.submit');
Route::get('/login/as/{role}', [LoginController::class, 'loginAsRole'])->name('login.as');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('guest')->group(function () {
    Route::get('/create-student-account', [StudentRegistrationController::class, 'create'])
        ->name('student.registration.request');
    Route::post('/create-student-account', [StudentRegistrationController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('student.registration.store');
    Route::get('/create-student-account/verify/{token}', [StudentRegistrationController::class, 'codeForm'])
        ->middleware('throttle:10,1')
        ->name('student.registration.code');
    Route::post('/create-student-account/verify', [StudentRegistrationController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('student.registration.verify');

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
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// Public, signed document verification endpoints used by printed QR codes.
Route::get('/verify', [DocumentVerificationController::class, 'lookup'])->name('documents.lookup');
Route::get('/verify/{identifier}', [DocumentVerificationController::class, 'show'])
    ->where('identifier', '[A-Za-z0-9-]+')
    ->name('documents.verify');
Route::post('/verify/{identifier}/file', [DocumentVerificationController::class, 'verifyFile'])
    ->where('identifier', '[A-Za-z0-9-]+')
    ->name('documents.verify-file');

Route::middleware(['auth', 'student.verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // User Management
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
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
    Route::get('/student/request', [RequestController::class, 'studentIndex'])->name('student.request');
    Route::get('/student/my-requests', [RequestController::class, 'myRequests'])->name('student.my-requests');
    Route::post('/student/request/store', [RequestController::class, 'store'])->name('student.request.store');

    // Records Officer / Registrar Request Management
    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/history', [RequestController::class, 'history'])->name('requests.history');
    Route::post('/requests/{request_id}/update-status', [RequestController::class, 'updateStatus'])->name('requests.update-status');
    Route::post('/requests/{request_id}/confirm-payment', [RequestController::class, 'confirmPayment'])->name('requests.confirm-payment');
    Route::post('/requests/{request_id}/update-clearance', [RequestController::class, 'updateClearance'])->name('requests.update-clearance');
    Route::delete('/requests/history/clear', [RequestController::class, 'clearHistory'])->name('requests.history.clear');
    Route::delete('/requests/reset-all', [RequestController::class, 'resetAll'])->name('requests.reset-all');

    // Integrated F137/F138 school-forms module
    Route::get('/school-forms', [SchoolFormController::class, 'home'])->name('school-forms.home');
    Route::get('/school-forms/request/{documentRequest?}', [GeneratorController::class, 'maker'])->name('generator.maker');
    Route::get('/school-forms/grade-sheets', [GeneratorController::class, 'gradeSheets'])->name('generator.grade-sheets');
    Route::post('/school-forms/grade-sheets', [SchoolFormF138Controller::class, 'storeGradeSheets'])->name('school-forms.grade-sheets.store');
    Route::get('/school-forms/records', [SchoolFormRecordController::class, 'index'])->name('school-forms.records');
    Route::get('/school-forms/uploads/{upload}', [SchoolFormRecordController::class, 'download'])->name('school-forms.uploads.download');
    Route::get('/school-forms/uploads/{upload}/preview', [SchoolFormRecordController::class, 'preview'])->name('school-forms.uploads.preview');
    Route::delete('/school-forms/uploads/{upload}', [SchoolFormRecordController::class, 'destroy'])->name('school-forms.uploads.destroy');
    Route::get('/school-forms/f137/preview', [SchoolFormF137Controller::class, 'preview'])->name('school-forms.f137.preview');
    Route::get('/school-forms/f137/download', [SchoolFormF137Controller::class, 'download'])->name('school-forms.f137.download');
    Route::get('/school-forms/f137/template', [SchoolFormF137Controller::class, 'template'])->name('school-forms.f137.template');
    Route::get('/school-forms/f138/preview', [SchoolFormF138Controller::class, 'preview'])->name('school-forms.f138.preview');
    Route::get('/school-forms/f138/pdf', [SchoolFormF138Controller::class, 'pdf'])->name('school-forms.f138.pdf');
    Route::get('/school-forms/f138/download', [SchoolFormF138Controller::class, 'download'])->name('school-forms.f138.download');
    Route::post('/school-forms/f138/finalize', [SchoolFormF138Controller::class, 'finalize'])->name('school-forms.f138.finalize');

    // Legacy Grade Portal
    Route::get('/grade-portal', [GradeController::class, 'index'])->name('grade-portal.index');
    Route::post('/grade-portal/upload', [GradeController::class, 'upload'])->name('grade-portal.upload');
    Route::delete('/grade-portal/delete/{id}', [GradeController::class, 'deleteUpload'])->name('grade-portal.delete');

    // Form 137 Maker (Records Officer)
    Route::get('/form-137', [Form137Controller::class, 'index'])->name('form-137.index');
    Route::post('/form-137/preview-manual', [Form137Controller::class, 'previewManual'])->name('form-137.preview-manual');
    Route::post('/form-137/generate-manual', [Form137Controller::class, 'generateManual'])->name('form-137.generate-manual');
    Route::get('/form-137/download/{student_number}', [Form137Controller::class, 'downloadExcel'])->name('form-137.download');
    Route::get('/form-137/view-html/{id}', [Form137Controller::class, 'viewHtml'])->name('form-137.view-html');

    // Form 138 Maker
    Route::get('/form-138', [Form138Controller::class, 'index'])->name('form-138.index');
    Route::post('/form-138/generate', [Form138Controller::class, 'generate'])->name('form-138.generate');

    // Certification Maker
    Route::get('/certifications', [CertificationController::class, 'index'])->name('certifications.index');
    Route::post('/certifications/preview', [CertificationController::class, 'preview'])->name('certifications.preview');
    Route::post('/certifications/pdf', [CertificationController::class, 'pdf'])->name('certifications.pdf');
    Route::post('/certifications/finalize', [CertificationController::class, 'finalize'])->name('certifications.finalize');

    // Good Moral Maker
    Route::get('/good-moral', [GoodMoralController::class, 'index'])->name('good-moral.index');
    Route::get('/good-moral/preview/{id}', [GoodMoralController::class, 'previewFromRequest'])->name('good-moral.preview-request');
    Route::post('/good-moral/generate', [GoodMoralController::class, 'generate'])->name('good-moral.generate');
    Route::post('/good-moral/submit', [GoodMoralController::class, 'submitToRegistrar'])->name('good-moral.submit');

    // Diploma Maker
    Route::get('/diploma', [DiplomaController::class, 'index'])->name('diploma.index');
    Route::get('/diploma/preview/{id}', [DiplomaController::class, 'previewFromRequest'])->name('diploma.preview-request');
    Route::post('/diploma/generate', [DiplomaController::class, 'generate'])->name('diploma.generate');
    Route::post('/diploma/submit', [DiplomaController::class, 'submitToRegistrar'])->name('diploma.submit');

    // System & Settings
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
    Route::delete('/logs/clear', [ActivityLogController::class, 'clear'])->name('logs.clear');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/documents/authenticity/{document}/revoke', [DocumentVerificationController::class, 'revoke'])
        ->name('documents.revoke');
    Route::get('/documents/authenticity/{document}/issued', [DocumentVerificationController::class, 'issued'])
        ->name('documents.issued');
    Route::get('/documents/authenticity/{document}/download', [DocumentVerificationController::class, 'downloadOfficial'])
        ->name('documents.download');
});
