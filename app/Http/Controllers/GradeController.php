<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Grade;
use Illuminate\Http\Request;

use App\Models\Form138Upload;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;

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
            'files.*' => 'required|file|mimes:xlsx,xls',
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
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('form138_uploads/' . $student->student_number, $filename, 'public');

            Form138Upload::create([
                'student_number' => $student->student_number,
                'school_year' => $schoolYear,
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
            ]);

            record_log('Uploaded Grade Sheet', 'Grades', "Uploaded Form 138 for {$student->student_number} (SY: {$schoolYear})");
        }

        return redirect()->back()->with('success', count($files) . ' Form 138 files uploaded successfully.');
    }
}
