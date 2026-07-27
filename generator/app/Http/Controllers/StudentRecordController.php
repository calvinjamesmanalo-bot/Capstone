<?php

namespace App\Http\Controllers;

use App\Models\GradeSheetUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentRecordController extends Controller
{
    public function index(Request $request)
    {
        $schoolYear = trim((string) $request->query('school_year'));
        $level = trim((string) $request->query('level'));
        $section = trim((string) $request->query('section'));
        $hasSearch = $schoolYear !== '' && $level !== '' && $section !== '';

        $schoolYears = GradeSheetUpload::distinct()->orderByDesc('school_year')->pluck('school_year');
        $levels = GradeSheetUpload::distinct()->orderBy('level')->pluck('level');
        $sections = GradeSheetUpload::distinct()->orderBy('section')->pluck('section');
        $uploads = GradeSheetUpload::query()
            ->when(!$hasSearch, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($hasSearch, fn ($query) => $query->where('school_year', $schoolYear)
                ->where('level', $level)->where('section', $section))
            ->orderBy('grading_period')->orderBy('file_type')->get();

        return view('students.index', compact(
            'schoolYear', 'level', 'section', 'hasSearch', 'schoolYears', 'levels', 'sections', 'uploads'
        ));
    }

    public function destroy(GradeSheetUpload $upload)
    {
        DB::transaction(function () use ($upload) {
            $enrollmentIds = DB::table('student_enrollments')->where([
                'school_year' => $upload->school_year, 'level' => $upload->level, 'section' => $upload->section,
            ])->pluck('id');
            $table = $upload->file_type === 'summary' ? 'student_grades' : 'student_attendance';
            DB::table($table)->whereIn('student_enrollment_id', $enrollmentIds)
                ->where('grading_period', $upload->grading_period)->delete();
            Storage::disk('local')->delete($upload->stored_path);
            $upload->delete();
        });

        return back()->with('status', 'Uploaded sheet and its imported records were deleted.');
    }

    public function download(GradeSheetUpload $upload)
    {
        abort_unless(Storage::disk('local')->exists($upload->stored_path), 404);

        return Storage::disk('local')->download($upload->stored_path, $upload->original_name);
    }
}
