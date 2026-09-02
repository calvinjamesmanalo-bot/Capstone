<?php

use App\Http\Controllers\Form137Controller;
use App\Http\Controllers\F138WorkflowController;
use App\Http\Controllers\StudentRecordController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Barryvdh\DomPDF\Facade\Pdf;

Route::get('/', fn () => view('welcome'))->name('home');

Route::post('/generate-f137', [Form137Controller::class, 'generate'])->name('generate-f137');
Route::get('/f137/preview', [Form137Controller::class, 'preview'])->name('f137.preview');
Route::get('/f137/download', [Form137Controller::class, 'download'])->name('f137.download');
Route::get('/f137/template', [Form137Controller::class, 'template'])->name('f137.template');
Route::post('/generate-f138', [Form137Controller::class, 'generateF138'])->name('generate-f138');
Route::post('/grade-sheets', [F138WorkflowController::class, 'storeGradeSheets'])->name('grade-sheets.store');
Route::get('/f138/preview', [F138WorkflowController::class, 'preview'])->name('f138.preview');
Route::get('/f138/pdf', [F138WorkflowController::class, 'pdf'])->name('f138.pdf');
Route::get('/f138/download', [F138WorkflowController::class, 'download'])->name('f138.download');
Route::get('/records', [StudentRecordController::class, 'index'])->name('students.index');
Route::delete('/records/uploads/{upload}', [StudentRecordController::class, 'destroy'])->name('students.uploads.destroy');
Route::get('/records/uploads/{upload}', [StudentRecordController::class, 'download'])->name('students.uploads.download');

Route::get('/f138-template-preview', function () {
    return Pdf::loadView('pdf.f138-template')
        ->setPaper('a4', 'portrait')
        ->stream('F138-Blank-Template.pdf');
})->name('f138-template-preview');

Route::get('/generated-f137-template', function () {
    $filePath = base_path('F137.xlsx');

    abort_unless(File::exists($filePath), 404);

    return response()->download(
        $filePath,
        'F137.xlsx',
        ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
    );
})->name('generated-f137-template');
