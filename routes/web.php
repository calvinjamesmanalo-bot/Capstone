<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\Form137Controller;
use App\Http\Controllers\GoodMoralController;
use App\Http\Controllers\DiplomaController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\SettingController;

use App\Http\Controllers\Auth\LoginController;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/login/as/{role}', [LoginController::class, 'loginAsRole'])->name('login.as');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // User Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Student Requests
    Route::get('/student/request', [RequestController::class, 'studentIndex'])->name('student.request');
    Route::post('/student/request/store', [RequestController::class, 'store'])->name('student.request.store');

    // Records Officer / Registrar Request Management
    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/history', [RequestController::class, 'history'])->name('requests.history');
    Route::post('/requests/{request_id}/update-status', [RequestController::class, 'updateStatus'])->name('requests.update-status');
    Route::delete('/requests/history/clear', [RequestController::class, 'clearHistory'])->name('requests.history.clear');

    // Grade Portal (Registrar)
    Route::get('/grade-portal', [GradeController::class, 'index'])->name('grade-portal.index');
    Route::post('/grade-portal/upload', [GradeController::class, 'upload'])->name('grade-portal.upload');
    Route::delete('/grade-portal/delete/{id}', [GradeController::class, 'deleteUpload'])->name('grade-portal.delete');

    // Form 137 Maker (Records Officer)
    Route::get('/form-137', [Form137Controller::class, 'index'])->name('form-137.index');
    Route::post('/form-137/preview-manual', [Form137Controller::class, 'previewManual'])->name('form-137.preview-manual');
    Route::post('/form-137/generate-manual', [Form137Controller::class, 'generateManual'])->name('form-137.generate-manual');
    Route::get('/form-137/download/{student_number}', [Form137Controller::class, 'downloadExcel'])->name('form-137.download');
    Route::get('/form-137/view-html/{id}', [Form137Controller::class, 'viewHtml'])->name('form-137.view-html');
    Route::get('/form-137/preview-request/{id}', [Form137Controller::class, 'previewRequest'])->name('form-137.preview-request');

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
    Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
});
