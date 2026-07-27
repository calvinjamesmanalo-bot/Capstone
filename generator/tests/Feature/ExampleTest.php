<?php

namespace Tests\Feature;

use App\Models\GradeSheetUpload;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_blank_f138_template_preview_is_a_pdf(): void
    {
        $response = $this->get('/f138-template-preview');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_blank_f137_template_is_the_excel_workbook(): void
    {
        $response = $this->get(route('f137.template'));

        $response->assertOk();
        $response->assertDownload('F137-Blank-Template.xlsx');
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', file_get_contents($response->baseResponse->getFile()->getPathname()));
    }

    public function test_student_f137_is_generated_as_a_populated_excel_workbook(): void
    {
        $student = Student::create([
            'student_number' => '2020-0001',
            'lrn' => '424413240015',
            'name' => 'Dela Cruz, Juan Santos',
        ]);
        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'school_year' => '2020-2021',
            'level' => 'Grade 1',
            'section' => 'Amity',
            'adviser_name' => 'Miss Criz Joy D. Marzol',
        ]);
        StudentGrade::create([
            'student_enrollment_id' => $enrollment->id,
            'grading_period' => 1,
            'learning_area' => 'Mathematics',
            'grade' => 90,
        ]);

        $this->get(route('f137.preview', ['student' => '2020-0001']))
            ->assertOk()
            ->assertSee('F137 Preview')
            ->assertSee('Mathematics')
            ->assertSee('90');

        $response = $this->get(route('f137.download', ['student' => '2020-0001']));

        $response->assertOk();
        $response->assertDownload('F137-2020-0001.xlsx');
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $content = file_get_contents($response->baseResponse->getFile()->getPathname());
        $this->assertStringStartsWith('PK', $content);

        $path = tempnam(sys_get_temp_dir(), 'f137-test-');
        file_put_contents($path, $content);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        $worksheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('Dela Cruz', $worksheet);
        $this->assertStringContainsString('2020-2021', $worksheet);
        $this->assertStringContainsString('Miss Criz Joy D. Marzol', $worksheet);
        $this->assertStringContainsString('<c r="G33"', $worksheet);
        $this->assertMatchesRegularExpression('/<c r="G33"[^>]*><v>90(?:\.0+)?<\/v><\/c>/', $worksheet);
        $this->assertStringNotContainsString('<v>80.86</v>', $worksheet);
    }

    public function test_the_same_f137_can_be_generated_using_a_formatted_lrn(): void
    {
        $student = Student::create([
            'student_number' => '2020-0001',
            'lrn' => '424413240015',
            'name' => 'Dela Cruz, Juan Santos',
        ]);
        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'school_year' => '2020-2021',
            'level' => 'Grade 1',
            'section' => 'Amity',
        ]);
        StudentGrade::create([
            'student_enrollment_id' => $enrollment->id,
            'grading_period' => 1,
            'learning_area' => 'Mathematics',
            'grade' => 90,
        ]);

        $this->get(route('f137.download', ['student' => '4244-1324-0015']))
            ->assertOk()
            ->assertDownload('F137-2020-0001.xlsx');
    }

    public function test_uploaded_records_can_reconcile_name_formats_into_one_student(): void
    {
        $student = Student::create([
            'student_number' => '2020-0001',
            'name' => 'Dela Cruz, Juan Santos',
        ]);

        $matched = Student::findByName('Juan Santos Dela Cruz');

        $this->assertTrue($student->is($matched));
    }

    public function test_grade_sheet_uploader_uses_the_average_file_field_expected_by_the_controller(): void
    {
        $response = $this->get('/records');

        $response->assertOk();
        $response->assertSee('name="average_files[0][1]"', false);
        $response->assertDontSee('name="summary_files[0][1]"', false);
    }

    public function test_record_finder_searches_by_class_and_f138_accepts_student_identifiers(): void
    {
        $this->get('/records')
            ->assertOk()
            ->assertSee('Record Finder')
            ->assertSee('School year')
            ->assertSee('Grade level')
            ->assertSee('Section');

        $this->get('/')
            ->assertOk()
            ->assertSee('name="student"', false)
            ->assertSee('2020-0001');
    }

    public function test_record_finder_shows_downloadable_class_uploads_and_can_delete_sheet(): void
    {
        Storage::fake('local');
        $student = Student::create(['student_number' => '2020-0001', 'lrn' => '424413240015', 'name' => 'Test Student']);
        StudentEnrollment::create(['student_id' => $student->id, 'school_year' => '2020-2021', 'level' => 'Grade 1', 'section' => 'Amity']);
        $enrollment = $student->enrollments()->first();
        StudentGrade::create(['student_enrollment_id' => $enrollment->id, 'grading_period' => 1, 'learning_area' => 'Language', 'grade' => 90]);
        Storage::disk('local')->put('grade-sheets/test.xlsx', 'excel');
        $upload = GradeSheetUpload::create([
            'school_year' => '2020-2021', 'level' => 'Grade 1', 'section' => 'Amity',
            'grading_period' => 1, 'file_type' => 'summary', 'original_name' => 'first-summary.xlsx',
            'stored_path' => 'grade-sheets/test.xlsx',
        ]);

        $this->get('/records?school_year=2020-2021&level=Grade+1&section=Amity')
            ->assertOk()->assertSee('first-summary.xlsx')->assertSee('Summary sheet');
        $this->get(route('students.uploads.download', $upload))->assertDownload('first-summary.xlsx');
        $this->from('/records?school_year=2020-2021&level=Grade+1&section=Amity')
            ->delete(route('students.uploads.destroy', $upload))->assertRedirect();

        $this->assertDatabaseHas('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('grade_sheet_uploads', ['id' => $upload->id]);
        $this->assertDatabaseMissing('student_grades', ['student_enrollment_id' => $enrollment->id, 'grading_period' => 1]);
    }
}
