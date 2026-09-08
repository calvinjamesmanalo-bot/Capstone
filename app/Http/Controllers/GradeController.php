<?php

namespace App\Http\Controllers;

use App\Models\Form138Upload;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $student = null;
        $uploads = [];
        if ($request->has('search_student')) {
            $student = Student::where('student_number', $request->search_student)->first();
            if ($student) {
                $uploads = Form138Upload::where('student_number', $student->student_number)
                    ->orderBy('school_year', 'desc')
                    ->get();
            }
        }

        return view('grade-portal.index', compact('student', 'uploads'));
    }

    public function deleteUpload($id)
    {
        $upload = Form138Upload::findOrFail($id);

        // Delete the actual files from storage
        if (Storage::disk('public')->exists($upload->file_path)) {
            Storage::disk('public')->delete($upload->file_path);
        }
        if ($upload->pdf_path && Storage::disk('public')->exists($upload->pdf_path)) {
            Storage::disk('public')->delete($upload->pdf_path);
        }

        $upload->delete();

        record_log('Deleted Grade Sheet', 'Grades', "Deleted Form 138 for {$upload->student_number} (SY: {$upload->school_year})", 'warning');

        return redirect()->back()->with('success', 'Form 138 record and associated PDF deleted successfully.');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
            'name' => 'required|string',
            'school_years.*' => 'required|string',
            'files.*' => 'required|file|mimes:xlsx,xls,pdf,jpg,jpeg,png',
        ]);

        // Find or create student
        $student = Student::firstOrCreate(
            ['student_number' => $request->student_number],
            ['name' => $request->name]
        );

        $schoolYears = $request->school_years;
        $files = $request->file('files');

        foreach ($files as $index => $file) {
            $schoolYear = $schoolYears[$index];
            $filename = time().'_'.$file->getClientOriginalName();
            $path = $file->storeAs('form138_uploads/'.$student->student_number, $filename, 'public');

            // Determine if it's a PDF for the pdf_path field
            $pdfPath = null;
            if ($file->getClientOriginalExtension() === 'pdf') {
                $pdfPath = $path;
            }

            Form138Upload::create([
                'student_number' => $student->student_number,
                'school_year' => $schoolYear,
                'file_path' => $path,
                'pdf_path' => $pdfPath,
                'original_filename' => $file->getClientOriginalName(),
            ]);

            record_log('Uploaded Reference File', 'Grades', "Uploaded reference Form 138 for {$student->student_number} (SY: {$schoolYear})");
        }

        return redirect()->back()->with('success', count($files).' reference file(s) uploaded successfully.');
    }
}
