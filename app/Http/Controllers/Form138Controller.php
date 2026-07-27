<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Grade;
use Illuminate\Http\Request;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

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
                $referenceFiles = \App\Models\Form138Upload::where('student_number', $studentNumber)
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
            'school_year' => 'required|string',
            'grade_level' => 'required|string',
            'subjects' => 'required|array',
        ]);

        $student = Student::where('name', $request->student_name)->first();
        
        $html = view('form-138.pdf-template', [
            'student' => $student,
            'student_name' => $request->student_name,
            'school_year' => $request->school_year,
            'grade_level' => $request->grade_level,
            'subjects' => $request->subjects
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "Form138_" . str_replace(' ', '_', $request->student_name) . ".pdf";
        return $dompdf->stream($filename, ["Attachment" => $request->has('download') ? true : false]);
    }
}
