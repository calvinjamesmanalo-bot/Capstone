<?php

namespace App\Http\Controllers;

use App\Models\Form138Upload;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

        $disk = $upload->storage_disk ?: 'public';

        if (Storage::disk($disk)->exists($upload->file_path)) {
            Storage::disk($disk)->delete($upload->file_path);
        }
        if ($upload->pdf_path && $upload->pdf_path !== $upload->file_path && Storage::disk($disk)->exists($upload->pdf_path)) {
            Storage::disk($disk)->delete($upload->pdf_path);
        }

        $upload->delete();

        record_log(
            'Deleted Grade File',
            'File Security',
            "Deleted grade upload #{$upload->id}; student: {$upload->student_number}; school year: {$upload->school_year}; role: ".auth()->user()->role,
            'warning'
        );

        return redirect()->back()->with('success', 'Form 138 record and associated PDF deleted successfully.');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
            'name' => 'required|string',
            'school_years' => 'required|array|min:1|max:10',
            'school_years.*' => ['required', 'string', Rule::in(config('academics.school_years', []))],
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|mimes:xlsx,xls,pdf,jpg,jpeg,png|max:20480',
        ]);

        abort_unless(count($request->school_years) === count($request->file('files', [])), 422);

        // Find or create student
        $student = Student::firstOrCreate(
            ['student_number' => $request->student_number],
            ['name' => $request->name]
        );

        $schoolYears = $request->school_years;
        $files = $request->file('files');

        foreach ($files as $index => $file) {
            $schoolYear = $schoolYears[$index];
            $filename = Str::uuid().'.'.$file->extension();
            $path = $file->storeAs(
                'form138_uploads/'.$student->student_number.'/'.str_replace('-', '_', $schoolYear),
                $filename,
                'local'
            );

            // Determine if it's a PDF for the pdf_path field
            $pdfPath = null;
            if ($file->getClientOriginalExtension() === 'pdf') {
                $pdfPath = $path;
            }

            $createdUpload = Form138Upload::create([
                'student_number' => $student->student_number,
                'school_year' => $schoolYear,
                'file_path' => $path,
                'pdf_path' => $pdfPath,
                'original_filename' => $file->getClientOriginalName(),
                'storage_disk' => 'local',
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'sha256' => hash_file('sha256', $file->getRealPath()),
            ]);

            record_log(
                'Uploaded Grade File',
                'File Security',
                "Uploaded grade file #{$createdUpload->id}; student: {$student->student_number}; school year: {$schoolYear}; MIME: {$createdUpload->mime_type}; size: {$createdUpload->file_size} bytes; role: ".auth()->user()->role
            );
        }

        return redirect()->back()->with('success', count($files).' reference file(s) uploaded successfully.');
    }

    public function view(Form138Upload $upload)
    {
        $user = request()->user();
        if (! in_array($user?->role, ['admin', 'registrar'], true)) {
            record_log(
                'Grade File Access Denied',
                'File Security',
                "Denied grade upload #{$upload->id}; student: {$upload->student_number}; role: ".($user?->role ?? 'unknown'),
                'denied'
            );
            abort(403);
        }

        $disk = $upload->storage_disk ?: 'public';
        if (! in_array($disk, ['local', 'public'], true) || ! Storage::disk($disk)->exists($upload->file_path)) {
            record_log(
                'Grade File Missing',
                'File Security',
                "Grade upload #{$upload->id} is unavailable; student: {$upload->student_number}; role: {$user->role}",
                'missing'
            );
            abort(404);
        }

        record_log(
            'Viewed Grade File',
            'File Security',
            "Viewed grade upload #{$upload->id}; student: {$upload->student_number}; school year: {$upload->school_year}; MIME: ".($upload->mime_type ?: 'unknown').'; size: '.($upload->file_size ?? 'unknown')." bytes; role: {$user->role}"
        );

        return Storage::disk($disk)->response($upload->file_path, $upload->original_filename, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
