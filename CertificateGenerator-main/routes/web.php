<?php

use App\Http\Controllers\CertificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CertificationController::class, 'index']);
Route::get('/certifications', [CertificationController::class, 'index'])->name('certifications.index');
Route::post('/certifications/preview', [CertificationController::class, 'preview'])->name('certifications.preview');
Route::post('/certifications/pdf', [CertificationController::class, 'pdf'])->name('certifications.pdf');
