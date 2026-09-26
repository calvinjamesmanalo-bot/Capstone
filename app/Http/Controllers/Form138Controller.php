<?php

namespace App\Http\Controllers;

use App\Models\Form138Upload;
use App\Models\Grade;
use App\Models\Student;
use App\Support\DocumentQrCode;
use App\Support\GeneratedPdfProtection;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class Form138Controller extends Controller
{
    public function index(Request $request)
    {
        $students = Student::orderBy('name')->get();
        $selectedStudent = null;
        $studentNumber = null;
        $schoolYear = null;
        $gradeLevel = null;
        $grades = [];
        $referenceFiles = [];

        if ($request->has('student_number')) {
            $student = Student::where('student_number', $request->student_number)->first();
            if ($student) {
                $selectedStudent = $student->name;
                $studentNumber = $student->student_number;

                // Fetch reference files for this student
                $referenceFiles = Form138Upload::where('student_number', $studentNumber)
                    ->orderBy('school_year', 'desc')
                    ->get();

                $latestGrades = Grade::where('student_number', $studentNumber)
                    ->orderBy('school_year', 'desc')
                    ->get();

                if ($latestGrades->isNotEmpty()) {
                    $schoolYear = $latestGrades->first()->school_year;
                    $gradeLevel = $latestGrades->first()->grade_level;
                    $grades = $latestGrades;
                }
            }
        }

        return view('form-138.index', compact('students', 'selectedStudent', 'studentNumber', 'schoolYear', 'gradeLevel', 'grades', 'referenceFiles'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'student_name' => 'required|string',
            'request_id' => 'nullable|integer|exists:request_documents,id',
            'school_year' => 'required|string',
            'grade_level' => 'required|string',
            'subjects' => 'required|array',
        ]);

        $student = Student::where('name', $request->student_name)->first();
        $documentQr = app(DocumentQrCode::class)->make('Form 138', $request->student_name, [
            'request_id' => $request->integer('request_id') ?: null,
            'holder_identifier' => $student?->student_number,
            'issued_at' => now(),
            'fields' => [
                'school_year' => $request->school_year,
                'grade_level' => $request->grade_level,
                'subjects' => $request->subjects,
            ],
        ]);
        $html = view('form-138.pdf-template', [
            'student' => $student,
            'student_name' => $request->student_name,
            'school_year' => $request->school_year,
            'grade_level' => $request->grade_level,
            'subjects' => $request->subjects,
            'documentMode' => 'generated',
            'documentQr' => $documentQr,
            'qrContext' => [
                'qrRequestId' => $request->integer('request_id') ?: null,
                'qrHolderIdentifier' => $student?->student_number,
                'qrIssuedAt' => now(),
            ],
        ])->render();

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Form138_'.str_replace(' ', '_', $request->student_name).'.pdf';
        $bytes = app(GeneratedPdfProtection::class)->protect($documentQr['document'], $dompdf->output(), $filename);
        $disposition = $request->has('download') ? 'attachment' : 'inline';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
