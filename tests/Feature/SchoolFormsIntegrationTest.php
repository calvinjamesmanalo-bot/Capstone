<?php

namespace Tests\Feature;

use App\Http\Controllers\SchoolFormF138Controller;
use App\Models\DocumentAuthenticity;
use App\Models\RequestDocument;
use App\Models\SchoolFormEnrollment;
use App\Models\SchoolFormGrade;
use App\Models\SchoolFormStudent;
use App\Models\SchoolFormUpload;
use App\Models\Student as RequestStudent;
use App\Models\User;
use App\Support\GradeSheetImporter;
use App\Support\XlsxWorkbookReader;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi;
use Tests\TestCase;
use ZipArchive;

class SchoolFormsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.school_forms.database' => ':memory:']);
        DB::purge('school_forms');

        Schema::connection('school_forms')->create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_number')->nullable()->unique();
            $table->string('lrn')->nullable()->unique();
            $table->string('name');
            $table->timestamps();
        });
        Schema::connection('school_forms')->create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id');
            $table->string('school_year');
            $table->string('level');
            $table->string('section');
            $table->string('adviser_name')->nullable();
            $table->timestamps();
        });
        Schema::connection('school_forms')->create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id');
            $table->unsignedTinyInteger('grading_period');
            $table->string('learning_area');
            $table->decimal('grade', 5, 2)->nullable();
            $table->timestamps();
        });
        Schema::connection('school_forms')->create('student_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id');
            $table->unsignedTinyInteger('grading_period');
            $table->string('month');
            $table->unsignedSmallInteger('school_days')->nullable();
            $table->unsignedSmallInteger('days_present')->nullable();
            $table->timestamps();
        });
        Schema::connection('school_forms')->create('grade_sheet_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('school_year');
            $table->string('level');
            $table->string('section');
            $table->unsignedTinyInteger('grading_period');
            $table->string('file_type');
            $table->string('original_name');
            $table->string('stored_path');
            $table->timestamps();
        });

        $student = SchoolFormStudent::create([
            'student_number' => '2020-0001',
            'lrn' => '424413240015',
            'name' => 'Dela Cruz, Juan Santos',
        ]);
        $enrollment = SchoolFormEnrollment::create([
            'student_id' => $student->id,
            'school_year' => '2020-2021',
            'level' => 'Grade 1',
            'section' => 'Amity',
            'adviser_name' => 'Louisse Chua',
        ]);
        SchoolFormGrade::create([
            'student_enrollment_id' => $enrollment->id,
            'grading_period' => 1,
            'learning_area' => 'Mathematics',
            'grade' => 90,
        ]);
    }

    public function test_f137_and_f138_run_inside_reghub_without_port_8001(): void
    {
        $staff = User::factory()->create(['role' => 'records_officer']);
        $parameters = ['student' => '2020-0001'];

        $this->actingAs($staff)->get(route('school-forms.home'))
            ->assertOk()
            ->assertSee('F137 Maker')
            ->assertSee('F138 Maker')
            ->assertSee('id="fla-page-loader"', false);

        $this->get(route('school-forms.f137.preview', $parameters))
            ->assertOk()
            ->assertSee('F137 Draft Preview')
            ->assertSee('Learner Permanent Record for Elementary School')
            ->assertSee('Print preview')
            ->assertSee('Download F137 PDF')
            ->assertSee('Download F137 Excel')
            ->assertSee('SCHOLASTIC RECORD')
            ->assertSee('Mathematics');

        $f137Pdf = $this->get(route('school-forms.f137.pdf', $parameters));
        $f137Pdf->assertOk()->assertDownload('F137-2020-0001.pdf');
        $this->assertStringStartsWith('%PDF', $f137Pdf->getContent());
        $this->assertPdfPageCount($f137Pdf->getContent(), 2);

        $f137 = $this->get(route('school-forms.f137.download', $parameters));
        $f137->assertOk()->assertDownload('F137-2020-0001.xlsx');
        $this->assertReadableF137Layout($f137->streamedContent());

        $f138Parameters = $parameters + ['school_year' => '2020-2021'];
        $this->get(route('school-forms.f138.preview', $f138Parameters))
            ->assertOk()
            ->assertSee('F138 Preview')
            ->assertSee('Mathematics');

        $this->get(route('school-forms.f138.download', $f138Parameters))
            ->assertOk()
            ->assertDownload('F138-2020-0001-DRAFT.pdf');

        $this->assertStringNotContainsString(':8001', route('school-forms.f137.preview', $parameters));
        $this->assertStringNotContainsString(':8001', route('school-forms.f138.preview', $f138Parameters));
    }

    public function test_certificate_form_uses_the_selected_students_imported_enrollment_options(): void
    {
        RequestStudent::create([
            'student_number' => '2020-0001',
            'name' => 'Juan Santos Dela Cruz',
        ]);
        $staff = User::factory()->create(['role' => 'records_officer']);

        $this->actingAs($staff)
            ->get(route('certifications.index', ['student_number' => '2020-0001']))
            ->assertOk()
            ->assertSee('id="grade_level"', false)
            ->assertSee('id="school_year"', false)
            ->assertSee('id="section"', false)
            ->assertSee('Grade 1')
            ->assertSee('2020-2021')
            ->assertSee('Amity')
            ->assertSee('Options come from the selected student')
            ->assertDontSee('<input name="grade_level"', false)
            ->assertDontSee('<input name="school_year"', false)
            ->assertDontSee('<input name="section"', false);
    }

    public function test_f137_excel_uses_dynamic_database_subjects_without_fixed_row_collisions(): void
    {
        $enrollment = SchoolFormEnrollment::where('school_year', '2020-2021')->sole();
        foreach ([
            ['period' => 1, 'area' => 'Art', 'grade' => 91],
            ['period' => 1, 'area' => 'Robotics', 'grade' => 93],
            ['period' => 2, 'area' => 'Robotics', 'grade' => 94],
        ] as $grade) {
            SchoolFormGrade::create([
                'student_enrollment_id' => $enrollment->id,
                'grading_period' => $grade['period'],
                'learning_area' => $grade['area'],
                'grade' => $grade['grade'],
            ]);
        }

        $staff = User::factory()->create(['role' => 'records_officer']);
        $response = $this->actingAs($staff)->get(route('school-forms.f137.download', ['student' => '2020-0001']));
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'dynamic-f137-');
        file_put_contents($path, $response->streamedContent());

        try {
            $sheet = app(XlsxWorkbookReader::class)->read($path)[0];
            $rows = collect($sheet['rows'])->keyBy('index');
            $cells = fn (int $row): array => collect($rows->get($row)['cells'] ?? [])->pluck('value', 'column')->all();

            $this->assertSame('Art', $cells(30)['B']);
            $this->assertSame('91', (string) $cells(30)['G']);
            $this->assertSame('Mathematics', $cells(31)['B']);
            $this->assertSame('90', (string) $cells(31)['G']);
            $this->assertSame('Robotics', $cells(32)['B']);
            $this->assertSame('93', (string) $cells(32)['G']);
            $this->assertSame('94', (string) $cells(32)['H']);
        } finally {
            @unlink($path);
        }
    }

    public function test_elementary_f137_keeps_old_quarters_and_uses_three_terms_only_for_2026_records(): void
    {
        $this->addEnrollment('2026-2027', 'Grade 2', [1 => 81, 2 => 90, 3 => 99, 4 => 40]);
        RequestStudent::create(['student_number' => '2020-0001', 'lrn' => '424413240015', 'name' => 'Dela Cruz, Juan Santos']);
        $request = RequestDocument::create([
            'student_number' => '2020-0001',
            'document_type' => 'Form 137',
            'school_level' => 'elementary',
            'status' => 'processing',
        ]);
        $staff = User::factory()->create(['role' => 'records_officer']);
        $parameters = ['student' => '2020-0001', 'request_id' => $request->id];

        $preview = $this->actingAs($staff)->get(route('school-forms.f137.preview', $parameters));
        $preview->assertOk()
            ->assertSee('Learner Permanent Record for Elementary School')
            ->assertSeeInOrder(['2020-2021', 'Quarterly Rating', '2026-2027', 'Term Rating']);

        $pdf = $this->get(route('school-forms.f137.pdf', $parameters));
        $pdf->assertOk()->assertDownload('F137-2020-0001.pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $response = $this->get(route('school-forms.f137.download', $parameters));
        $response->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'mixed-elementary-f137-');
        file_put_contents($path, $response->streamedContent());

        try {
            $front = collect(app(XlsxWorkbookReader::class)->read($path))->firstWhere('name', 'Front');
            $rows = collect($front['rows'])->keyBy('index');
            $cells = fn (int $row): array => collect($rows->get($row)['cells'] ?? [])->pluck('value', 'column')->all();

            $this->assertSame('90', (string) $cells(30)['G']);
            $this->assertSame('81', (string) $cells(30)['Z']);
            $this->assertSame('90', (string) $cells(30)['AA']);
            $this->assertSame('99', (string) $cells(30)['AB']);
            $this->assertArrayNotHasKey('X', $cells(30));
            $this->assertSame('90', (string) $cells(30)['AC']);

            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $sheetXml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            $this->assertStringContainsString('ref="B28:F29"', $sheetXml);
            $this->assertStringContainsString('ref="Q28:Y29"', $sheetXml);
            $this->assertStringContainsString('ref="Z28:AB28"', $sheetXml);
        } finally {
            @unlink($path);
        }
    }

    public function test_elementary_f137_populates_a_2026_record_on_the_back_sheet(): void
    {
        $this->addEnrollment('2021-2022', 'Grade 2', [1 => 82]);
        $this->addEnrollment('2022-2023', 'Grade 3', [1 => 83]);
        $this->addEnrollment('2023-2024', 'Grade 4', [1 => 84]);
        $this->addEnrollment('2026-2027', 'Grade 5', [1 => 95, 2 => 96, 3 => 97]);
        RequestStudent::create(['student_number' => '2020-0001', 'lrn' => '424413240015', 'name' => 'Dela Cruz, Juan Santos']);
        $request = RequestDocument::create([
            'student_number' => '2020-0001',
            'document_type' => 'Form 137',
            'school_level' => 'elementary',
            'status' => 'processing',
        ]);
        $staff = User::factory()->create(['role' => 'records_officer']);
        $parameters = [
            'student' => '2020-0001',
            'request_id' => $request->id,
        ];
        $pdf = $this->actingAs($staff)->get(route('school-forms.f137.pdf', $parameters));
        $pdf->assertOk()->assertDownload('F137-2020-0001.pdf');
        $this->assertPdfPageCount($pdf->getContent(), 2);

        $response = $this->get(route('school-forms.f137.download', $parameters));
        $path = tempnam(sys_get_temp_dir(), 'back-elementary-f137-');
        file_put_contents($path, $response->streamedContent());

        try {
            $back = collect(app(XlsxWorkbookReader::class)->read($path))->firstWhere('name', 'Back');
            $rows = collect($back['rows'])->keyBy('index');
            $cells = fn (int $row): array => collect($rows->get($row)['cells'] ?? [])->pluck('value', 'column')->all();

            $this->assertSame('2026-2027', $cells(5)['N']);
            $this->assertSame('Mathematics', $cells(10)['B']);
            $this->assertSame('95', (string) $cells(10)['H']);
            $this->assertSame('96', (string) $cells(10)['I']);
            $this->assertSame('97', (string) $cells(10)['J']);
        } finally {
            @unlink($path);
        }
    }

    public function test_jhs_f137_request_uses_one_file_with_legacy_quarters_and_2026_terms(): void
    {
        $this->addEnrollment('2025-2026', 'Grade 7', [1 => 80, 2 => 81, 3 => 82, 4 => 83]);
        $this->addEnrollment('2026-2027', 'Grade 8', [1 => 90, 2 => 91, 3 => 92, 4 => 40]);
        RequestStudent::create(['student_number' => '2020-0001', 'lrn' => '424413240015', 'name' => 'Dela Cruz, Juan Santos']);
        $request = RequestDocument::create([
            'student_number' => '2020-0001',
            'document_type' => 'Form 137',
            'school_level' => 'jhs',
            'status' => 'processing',
        ]);
        $staff = User::factory()->create(['role' => 'records_officer']);
        $parameters = ['student' => '2020-0001', 'request_id' => $request->id];

        $this->actingAs($staff)->get(route('school-forms.f137.preview', $parameters))
            ->assertOk()
            ->assertSee('Learner Permanent Record for Junior High School')
            ->assertSeeInOrder(['2025-2026', 'Quarterly Rating', '2026-2027', 'Term Rating'])
            ->assertDontSee('2020-2021');

        $response = $this->get(route('school-forms.f137.download', $parameters));
        $response->assertOk()->assertDownload('F137-2020-0001.xlsx');
        $path = tempnam(sys_get_temp_dir(), 'mixed-jhs-f137-');
        file_put_contents($path, $response->streamedContent());

        try {
            $front = collect(app(XlsxWorkbookReader::class)->read($path))->firstWhere('name', 'Front');
            $rows = collect($front['rows'])->keyBy('index');
            $cells = fn (int $row): array => collect($rows->get($row)['cells'] ?? [])->pluck('value', 'column')->all();

            $this->assertSame('2025-2026', $cells(22)['V']);
            $this->assertSame('80', (string) $cells(26)['Y']);
            $this->assertSame('81', (string) $cells(26)['AB']);
            $this->assertSame('82', (string) $cells(26)['AE']);
            $this->assertSame('83', (string) $cells(26)['AH']);
            $this->assertSame('2026-2027', $cells(50)['V']);
            $this->assertSame('90', (string) $cells(54)['Y']);
            $this->assertSame('91', (string) $cells(54)['AC']);
            $this->assertSame('92', (string) $cells(54)['AG']);
            $this->assertSame('91', (string) $cells(54)['AJ']);
        } finally {
            @unlink($path);
        }
    }

    public function test_f137_request_student_number_resolves_the_imported_lrn_and_opens_directly(): void
    {
        RequestStudent::create([
            'student_number' => '2022-0001',
            'lrn' => '424413240015',
            'name' => 'Dela Cruz, Juan Santos',
        ]);
        $documentRequest = RequestDocument::create([
            'student_number' => '2022-0001',
            'document_type' => 'Form 137',
            'status' => 'processing',
        ]);
        $staff = User::factory()->create(['role' => 'records_officer']);
        $previewParameters = [
            'student' => '2022-0001',
            'request_id' => $documentRequest->id,
        ];

        $response = $this->actingAs($staff)->get(route('generator.maker', [
            'documentRequest' => $documentRequest,
            'form' => 'f137',
        ]));
        $response->assertRedirect(route('school-forms.f137.preview', $previewParameters));

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('F137 Draft Preview')
            ->assertSee('2022-0001')
            ->assertSee('Mathematics');

        $this->get(route('school-forms.f137.download', ['student' => '2022-0001']))
            ->assertOk()
            ->assertDownload('F137-2022-0001.xlsx');
    }

    public function test_f138_request_student_number_resolves_the_imported_lrn_and_opens_the_preview_directly(): void
    {
        $staff = User::factory()->create(['role' => 'records_officer']);
        $enrollment = SchoolFormEnrollment::where('school_year', '2020-2021')->sole();
        SchoolFormGrade::create([
            'student_enrollment_id' => $enrollment->id,
            'grading_period' => 2,
            'learning_area' => 'MATHEMATICS',
            'grade' => 88,
        ]);
        SchoolFormGrade::create([
            'student_enrollment_id' => $enrollment->id,
            'grading_period' => 1,
            'learning_area' => 'Good Manners and Right Conduct',
            'grade' => 85,
        ]);
        SchoolFormGrade::create([
            'student_enrollment_id' => $enrollment->id,
            'grading_period' => 3,
            'learning_area' => 'GMRC',
            'grade' => 91,
        ]);
        SchoolFormGrade::create([
            'student_enrollment_id' => $enrollment->id,
            'grading_period' => 3,
            'learning_area' => 'GEN. AVE.',
            'grade' => 90,
        ]);
        RequestStudent::create([
            'student_number' => '2022-0001',
            'lrn' => '424413240015',
            'name' => 'Test Student',
        ]);
        $documentRequest = RequestDocument::create([
            'student_number' => '2022-0001',
            'document_type' => 'Form 138',
            'school_year' => '2020-2021',
            'status' => 'processing',
        ]);
        $previewParameters = [
            'student' => '2022-0001',
            'school_year' => '2020-2021',
            'request_id' => $documentRequest->id,
        ];

        $response = $this->actingAs($staff)->get(route('generator.maker', [
            'documentRequest' => $documentRequest,
            'form' => 'f138',
        ]));
        $response->assertRedirect(route('school-forms.f138.preview', $previewParameters));

        $preview = $this->get($response->headers->get('Location'));
        $preview
            ->assertOk()
            ->assertSee('F138 Draft Preview')
            ->assertSee('2022-0001')
            ->assertSeeInOrder(['Mathematics', '90', '88'])
            ->assertSeeInOrder(['Good Manners and Right Conduct', '85', '91'])
            ->assertDontSee('GEN. AVE.');

        $this->assertSame(1, substr_count($preview->getContent(), '>Mathematics<'));
        $this->assertSame(1, substr_count($preview->getContent(), '>Good Manners and Right Conduct<'));
    }

    public function test_2020_f138_preview_keeps_four_quarter_columns(): void
    {
        $staff = User::factory()->create(['role' => 'records_officer']);

        $this->actingAs($staff)
            ->get(route('school-forms.f138.preview', [
                'student' => '2020-0001',
                'school_year' => '2020-2021',
            ]))
            ->assertOk()
            ->assertSeeInOrder(['Q1', 'Q2', 'Q3', 'Q4'])
            ->assertDontSee('Term 1');

        $method = new \ReflectionMethod(SchoolFormF138Controller::class, 'pdfView');
        $this->assertSame(
            'school-forms.pdf.f138-template',
            $method->invoke(app(SchoolFormF138Controller::class), '2020-2021'),
        );
    }

    public function test_2026_f138_preview_and_pdf_template_use_exactly_three_terms(): void
    {
        $student = SchoolFormStudent::where('student_number', '2020-0001')->sole();
        $enrollment = SchoolFormEnrollment::create([
            'student_id' => $student->id,
            'school_year' => '2026-2027',
            'level' => 'Grade 10',
            'section' => 'Bambi',
            'adviser_name' => 'Test Adviser',
        ]);
        foreach ([1 => 81, 2 => 90, 3 => 99, 4 => 40] as $period => $grade) {
            SchoolFormGrade::create([
                'student_enrollment_id' => $enrollment->id,
                'grading_period' => $period,
                'learning_area' => 'Mathematics',
                'grade' => $grade,
            ]);
        }
        $staff = User::factory()->create(['role' => 'records_officer']);

        $preview = $this->actingAs($staff)->get(route('school-forms.f138.preview', [
            'student' => '2020-0001',
            'school_year' => '2026-2027',
        ]));

        $preview->assertOk()
            ->assertSeeInOrder(['Term 1', 'Term 2', 'Term 3'])
            ->assertDontSee('Q4')
            ->assertDontSee('Term 4')
            ->assertSeeInOrder(['Mathematics', '81', '90', '99']);
        $this->assertSame(90, (int) $preview->viewData('record')['general_average']);

        $method = new \ReflectionMethod(SchoolFormF138Controller::class, 'pdfView');
        $pdfView = $method->invoke(app(SchoolFormF138Controller::class), '2026-2027');
        $pdfHtml = view($pdfView, [
            'student' => $student,
            'enrollment' => $enrollment,
            'gradeMatrix' => ['Mathematics' => [1 => 81, 2 => 90, 3 => 99]],
            'attendance' => [],
            'requestId' => null,
            'documentMode' => 'draft',
            'documentQr' => null,
        ])->render();

        $this->assertSame('school-forms.pdf.f138-three-term-template', $pdfView);
        $this->assertStringContainsString('TERM 1', $pdfHtml);
        $this->assertStringContainsString('TERM 3', $pdfHtml);
        $this->assertStringNotContainsString('TERM 4', $pdfHtml);
        $this->assertStringContainsString('COMPLETED JUNIOR HIGH SCHOOL', $pdfHtml);
        $this->assertStringContainsString('DRAFT', $pdfHtml);

        $pdfParameters = ['student' => '2020-0001', 'school_year' => '2026-2027'];
        $this->get(route('school-forms.f138.pdf', $pdfParameters))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="F138-2020-0001-DRAFT.pdf"');
        $this->get(route('school-forms.f138.download', $pdfParameters))
            ->assertOk()
            ->assertDownload('F138-2020-0001-DRAFT.pdf');

        $officialHtml = view($pdfView, [
            'student' => $student,
            'enrollment' => $enrollment,
            'gradeMatrix' => ['Mathematics' => [1 => 81, 2 => 90, 3 => 99]],
            'attendance' => [],
            'requestId' => 138,
            'documentMode' => 'official',
            'documentQr' => [
                'data_uri' => 'data:image/png;base64,AA==',
                'reference' => 'F138-TEST',
                'document' => (object) ['content_hash' => str_repeat('a', 64)],
            ],
        ])->render();

        $this->assertStringContainsString('SCAN TO VERIFY', $officialHtml);
        $this->assertStringContainsString('PDF DIGITALLY SIGNED', $officialHtml);
        $this->assertStringNotContainsString('NOT YET OFFICIALLY ISSUED', $officialHtml);
    }

    public function test_existing_f138_preview_finalizes_into_a_signed_immutable_official_pdf(): void
    {
        Storage::fake('local');
        $certificateDirectory = $this->configureSigningCertificate();

        try {
            RequestStudent::create(['student_number' => '2020-0001', 'name' => 'Dela Cruz, Juan Santos']);
            $documentRequest = RequestDocument::create([
                'student_number' => '2020-0001',
                'document_type' => 'Form 138',
                'school_year' => '2020-2021',
                'status' => 'processing',
            ]);
            $staff = User::factory()->create(['role' => 'records_officer']);
            $parameters = [
                'student' => '2020-0001',
                'school_year' => '2020-2021',
                'request_id' => $documentRequest->id,
            ];

            $this->actingAs($staff)
                ->get(route('school-forms.f138.preview', $parameters))
                ->assertOk()
                ->assertSee('F138 Draft Preview')
                ->assertSee('Finalize &amp; Issue', false);
            $this->assertSame(0, DocumentAuthenticity::count());

            $response = $this->post(route('school-forms.f138.finalize'), $parameters);
            $document = DocumentAuthenticity::with('officialArtifact')->sole();
            $signedPdf = Storage::disk('local')->get($document->officialArtifact->storage_path);

            $response->assertRedirect(route('documents.issued', $document));
            $this->assertSame('signed', $document->pdf_signature_status);
            $this->assertStringContainsString('/ByteRange', $signedPdf);
            $this->assertSame(hash('sha256', $signedPdf), $document->officialArtifact->sha256_hash);
            $this->assertSame('ready_to_release', $documentRequest->fresh()->status);
        } finally {
            foreach (['certificate.crt', 'private.key'] as $file) {
                $path = $certificateDirectory.DIRECTORY_SEPARATOR.$file;
                if (is_file($path)) {
                    unlink($path);
                }
            }
            if (is_dir($certificateDirectory)) {
                rmdir($certificateDirectory);
            }
        }
    }

    public function test_import_cannot_change_an_existing_enrollments_grade_for_the_same_school_year(): void
    {
        $student = SchoolFormStudent::where('student_number', '2020-0001')->sole();

        try {
            SchoolFormEnrollment::resolveForImport($student, '2020-2021', 'Grade 2', 'Amity', 'Another Adviser');
            $this->fail('A conflicting grade-level import should have been rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Enrollment conflict', $exception->getMessage());
        }

        $enrollment = $student->enrollments()->where('school_year', '2020-2021')->sole();
        $this->assertSame('Grade 1', $enrollment->level);
        $this->assertSame('Louisse Chua', $enrollment->adviser_name);
    }

    public function test_import_checks_the_school_year_grade_and_period_inside_the_workbook(): void
    {
        $reader = new class extends XlsxWorkbookReader
        {
            public function read(string $filePath): array
            {
                return [[
                    'name' => 'Sheet 1',
                    'rows' => [[
                        'index' => 3,
                        'cells' => [
                            ['column' => 'A', 'value' => 'Academic Year 2021-2022'],
                            ['column' => 'B', 'value' => 'Grade Four - Bambi'],
                            ['column' => 'C', 'value' => 'THIRD GRADING'],
                        ],
                    ]],
                ]];
            }
        };
        $importer = new GradeSheetImporter($reader);

        $importer->assertMatchesSelection('unused.xlsx', '2021-2022', 'Grade 4', 3);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Workbook metadata mismatch');
        $importer->assertMatchesSelection('unused.xlsx', '2020-2021', 'Grade 5', 3);
    }

    public function test_import_recognizes_terms_starting_with_school_year_2026_2027(): void
    {
        $reader = new class extends XlsxWorkbookReader
        {
            public function read(string $filePath): array
            {
                return [[
                    'name' => 'Sheet 1',
                    'rows' => [[
                        'index' => 3,
                        'cells' => [
                            ['column' => 'A', 'value' => 'Academic Year 2026-2027'],
                            ['column' => 'B', 'value' => 'Grade Four - Bambi'],
                            ['column' => 'C', 'value' => 'TERM 3'],
                        ],
                    ]],
                ]];
            }
        };

        (new GradeSheetImporter($reader))->assertMatchesSelection('unused.xlsx', '2026-2027', 'Grade 4', 3);

        $this->addToAssertionCount(1);
    }

    public function test_2026_2027_uploader_uses_three_terms_and_rejects_a_fourth_period(): void
    {
        Storage::fake('school_forms_local');
        $recordsOfficer = User::factory()->create(['role' => 'records_officer']);
        $query = ['school_year' => '2026-2027', 'level' => 'Grade 4', 'section' => 'Bambi'];

        $this->actingAs($recordsOfficer)
            ->get(route('school-forms.records', $query))
            ->assertOk()
            ->assertSee('Single-term upload')
            ->assertSee('Term to upload')
            ->assertSee('First term')
            ->assertSee('Third term')
            ->assertSee('0/6');

        $this->getJson(route('school-forms.grade-sheets.status', $query))
            ->assertOk()
            ->assertJsonPath('total', 6);

        $this->from(route('school-forms.records', $query))
            ->post(route('school-forms.grade-sheets.store'), [
                'grade_school_year' => '2026-2027',
                'grade_level' => 'Grade 4',
                'grade_section' => 'Bambi',
                'summary_files' => [
                    4 => UploadedFile::fake()->create('fourth-term.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
                ],
            ])
            ->assertRedirect(route('school-forms.records', $query))
            ->assertSessionHasErrors('grade_sheets');

        $this->assertDatabaseCount('grade_sheet_uploads', 0, 'school_forms');
    }

    public function test_2026_generated_grade_sheet_templates_use_term_metadata_and_months(): void
    {
        $staff = User::factory()->create(['role' => 'records_officer']);
        $query = ['school_year' => '2026-2027', 'level' => 'Grade 4', 'section' => 'Bambi'];

        $summary = $this->actingAs($staff)->get(route('school-forms.grade-sheets.template', [
            'type' => 'summary',
            'period' => 3,
        ] + $query));
        $summary->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'term-summary-');
        file_put_contents($path, $summary->streamedContent());
        try {
            app(GradeSheetImporter::class)->assertMatchesSelection($path, '2026-2027', 'Grade 4', 3);
        } finally {
            @unlink($path);
        }

        $attendance = $this->get(route('school-forms.grade-sheets.template', [
            'type' => 'attendance',
            'period' => 1,
        ] + $query));
        $attendance->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'term-attendance-');
        file_put_contents($path, $attendance->streamedContent());
        try {
            $sheet = app(XlsxWorkbookReader::class)->read($path)[0];
            $heading = collect($sheet['rows'])->firstWhere('index', 3);
            $months = collect($heading['cells'])->pluck('value')->all();
            $this->assertSame(['No.', 'LRN', 'Learner name', 'June', 'July', 'August', 'September'], $months);
        } finally {
            @unlink($path);
        }

        $this->get(route('school-forms.grade-sheets.template', [
            'type' => 'summary',
            'period' => 4,
        ] + $query))->assertSessionHasErrors('period');
    }

    public function test_summary_subjects_and_grades_are_read_from_the_uploaded_sheet(): void
    {
        $reader = new class extends XlsxWorkbookReader
        {
            public function read(string $filePath): array
            {
                return [['name' => 'Summary', 'rows' => [
                    ['index' => 6, 'cells' => [
                        ['column' => 'B', 'value' => 'Student Number'],
                        ['column' => 'C', 'value' => 'Name'],
                        ['column' => 'D', 'value' => 'Science'],
                        ['column' => 'E', 'value' => 'Filipino'],
                        ['column' => 'F', 'value' => 'General Average'],
                    ]],
                    ['index' => 7, 'cells' => [
                        ['column' => 'B', 'value' => '2026-001'],
                        ['column' => 'C', 'value' => 'Test Student'],
                        ['column' => 'D', 'value' => '91'],
                        ['column' => 'E', 'value' => '88'],
                        ['column' => 'F', 'value' => '89.5'],
                    ]],
                ]]];
            }
        };

        $records = (new GradeSheetImporter($reader))->summaries('unused.xlsx');

        $this->assertSame(['Science' => 91.0, 'Filipino' => 88.0], $records[0]['grades']);
    }

    public function test_attendance_months_are_read_from_each_workbooks_actual_headings(): void
    {
        $reader = new class extends XlsxWorkbookReader
        {
            public function read(string $filePath): array
            {
                return [['name' => 'Attendance', 'rows' => [
                    ['index' => 6, 'cells' => [
                        ['column' => 'D', 'value' => 'JUNE'],
                        ['column' => 'E', 'value' => 'JULY'],
                        ['column' => 'F', 'value' => 'AUGUST'],
                        ['column' => 'G', 'value' => 'Total'],
                    ]],
                    ['index' => 7, 'cells' => [
                        ['column' => 'D', 'value' => 12],
                        ['column' => 'E', 'value' => 25],
                        ['column' => 'F', 'value' => 19],
                    ]],
                    ['index' => 8, 'cells' => [
                        ['column' => 'B', 'value' => '424413240015'],
                        ['column' => 'C', 'value' => 'Test Student'],
                        ['column' => 'D', 'value' => 10],
                        ['column' => 'E', 'value' => 25],
                        ['column' => 'F', 'value' => 18],
                        ['column' => 'G', 'value' => 53],
                    ]],
                ]]];
            }
        };

        $records = (new GradeSheetImporter($reader))->attendance('unused.xlsx');

        $this->assertSame([
            'June' => ['school_days' => 12, 'days_present' => 10],
            'July' => ['school_days' => 25, 'days_present' => 25],
            'August' => ['school_days' => 19, 'days_present' => 18],
        ], $records[0]['months']);
    }

    public function test_registrar_can_quickly_preview_a_private_excel_sheet_without_downloading_it(): void
    {
        Storage::fake('school_forms_local');
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()
            ->setTitle('Summary Sheet')
            ->setCellValue('A1', 'Academic Year 2020-2021')
            ->setCellValue('B2', '2020-0001')
            ->setCellValue('C2', 'Alkuno, Seb Ezekiel A.')
            ->setCellValue('D2', 91);
        $storedPath = 'grade-sheets/preview/summary.xlsx';
        Storage::disk('school_forms_local')->makeDirectory(dirname($storedPath));
        (new Xlsx($spreadsheet))->save(Storage::disk('school_forms_local')->path($storedPath));
        $spreadsheet->disconnectWorksheets();

        $upload = SchoolFormUpload::create([
            'school_year' => '2020-2021',
            'level' => 'Grade 1',
            'section' => 'Amity',
            'grading_period' => 1,
            'file_type' => 'summary',
            'original_name' => 'Student_Summary_2020-2021_First_Grading.xlsx',
            'stored_path' => $storedPath,
        ]);
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)
            ->get(route('school-forms.uploads.preview', $upload))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertSee('Summary Sheet')
            ->assertSee('2020-0001')
            ->assertSee('Alkuno, Seb Ezekiel A.')
            ->assertSee('Read-only preview');

        $this->actingAs($registrar)
            ->get(route('school-forms.records', [
                'school_year' => '2020-2021',
                'level' => 'Grade 1',
                'section' => 'Amity',
            ]))
            ->assertOk()
            ->assertSee('Quick View')
            ->assertSee(route('school-forms.uploads.preview', $upload), false);

        $recordsOfficer = User::factory()->create(['role' => 'records_officer']);
        $this->actingAs($recordsOfficer)
            ->get(route('school-forms.uploads.preview', $upload))
            ->assertOk()
            ->assertSee('2020-0001');

        $this->actingAs($recordsOfficer)
            ->get(route('generator.grade-sheets'))
            ->assertRedirect(route('school-forms.records'));

        $this->actingAs($recordsOfficer)
            ->get(route('school-forms.records', [
                'school_year' => '2020-2021',
                'level' => 'Grade 1',
                'section' => 'Amity',
            ]))
            ->assertOk()
            ->assertSee('Quick View')
            ->assertSee('Upload grade sheets')
            ->assertDontSee('Delete this sheet');
    }

    public function test_records_officer_can_upload_grade_sheets_but_cannot_delete_them(): void
    {
        Storage::fake('school_forms_local');
        $importer = $this->mock(GradeSheetImporter::class);
        $importer->shouldReceive('assertMatchesSelection')->twice();
        $importer->shouldReceive('teacherName')->twice()->andReturn('Test Adviser');
        $importer->shouldReceive('summaries')->once()->andReturn([]);
        $importer->shouldReceive('attendance')->once()->andReturn([]);
        $recordsOfficer = User::factory()->create(['role' => 'records_officer']);

        $this->actingAs($recordsOfficer)
            ->get(route('school-forms.records'))
            ->assertOk()
            ->assertSee('2010-2011')
            ->assertSee('2026-2027')
            ->assertSee('Elementary (Kinder to Grade 6)')
            ->assertSee('Junior High School (Grade 7 to Grade 10)')
            ->assertSee('Kinder')
            ->assertSee('Grade 6')
            ->assertSee('Grade 7')
            ->assertSee('Grade 10')
            ->assertSee('Quarterly upload')
            ->assertSee('Whole school year / bulk')
            ->assertSee('Grading period to upload');

        $this->actingAs($recordsOfficer)
            ->post(route('school-forms.grade-sheets.store'), [
                'grade_school_year' => '2010-2011',
                'grade_level' => 'Grade 2',
                'grade_section' => 'Bambi',
                'grading_period' => 2,
                'attendance_file' => UploadedFile::fake()->create('attendance.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
                'summary_file' => UploadedFile::fake()->create('summary.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ])
            ->assertRedirect(route('school-forms.records'));

        $this->assertDatabaseCount('grade_sheet_uploads', 2, 'school_forms');
        $upload = SchoolFormUpload::firstOrFail();
        $this->actingAs($recordsOfficer)
            ->delete(route('school-forms.uploads.destroy', $upload))
            ->assertForbidden();
    }

    public function test_staff_can_upload_multiple_optional_workbook_slots_in_one_batch(): void
    {
        Storage::fake('school_forms_local');
        $importer = $this->mock(GradeSheetImporter::class);
        $importer->shouldReceive('assertMatchesSelection')->twice();
        $importer->shouldReceive('teacherName')->twice()->andReturn('Test Adviser');
        $importer->shouldReceive('summaries')->twice()->andReturn([]);
        $recordsOfficer = User::factory()->create(['role' => 'records_officer']);

        $this->actingAs($recordsOfficer)
            ->post(route('school-forms.grade-sheets.store'), [
                'grade_school_year' => '2010-2011',
                'grade_level' => 'Grade 10',
                'grade_section' => 'Bambi',
                'summary_files' => [
                    1 => UploadedFile::fake()->create('first-summary.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
                    3 => UploadedFile::fake()->create('third-summary.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
                ],
            ])
            ->assertRedirect(route('school-forms.records', [
                'school_year' => '2010-2011',
                'level' => 'Grade 10',
                'section' => 'Bambi',
            ]))
            ->assertSessionHas('grade_sheet_import_result', fn (array $result) => $result['uploaded'] === 2 && $result['replaced'] === 0);

        $this->assertDatabaseCount('grade_sheet_uploads', 2, 'school_forms');
        $this->assertDatabaseHas('grade_sheet_uploads', ['grading_period' => 1, 'file_type' => 'summary'], 'school_forms');
        $this->assertDatabaseHas('grade_sheet_uploads', ['grading_period' => 3, 'file_type' => 'summary'], 'school_forms');
    }

    public function test_bulk_upload_requires_confirmation_before_replacing_a_stored_slot(): void
    {
        Storage::fake('school_forms_local');
        SchoolFormUpload::create([
            'school_year' => '2010-2011',
            'level' => 'Grade 2',
            'section' => 'Bambi',
            'grading_period' => 1,
            'file_type' => 'summary',
            'original_name' => 'original.xlsx',
            'stored_path' => 'grade-sheets/original.xlsx',
        ]);
        $importer = $this->mock(GradeSheetImporter::class);
        $importer->shouldReceive('assertMatchesSelection')->once();
        $recordsOfficer = User::factory()->create(['role' => 'records_officer']);

        $this->actingAs($recordsOfficer)
            ->from(route('school-forms.records'))
            ->post(route('school-forms.grade-sheets.store'), [
                'grade_school_year' => '2010-2011',
                'grade_level' => 'Grade 2',
                'grade_section' => 'Bambi',
                'summary_files' => [
                    1 => UploadedFile::fake()->create('replacement.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
                ],
            ])
            ->assertRedirect(route('school-forms.records'))
            ->assertSessionHasErrors('replace_existing');

        $this->assertDatabaseHas('grade_sheet_uploads', ['original_name' => 'original.xlsx'], 'school_forms');
    }

    public function test_upload_status_and_dynamic_templates_are_available_to_staff(): void
    {
        SchoolFormUpload::create([
            'school_year' => '2010-2011',
            'level' => 'Grade 10',
            'section' => 'Bambi',
            'grading_period' => 2,
            'file_type' => 'attendance',
            'original_name' => 'attendance.xlsx',
            'stored_path' => 'grade-sheets/attendance.xlsx',
        ]);
        $recordsOfficer = User::factory()->create(['role' => 'records_officer']);
        $query = ['school_year' => '2010-2011', 'level' => 'Grade 10', 'section' => 'Bambi'];

        $this->actingAs($recordsOfficer)
            ->getJson(route('school-forms.grade-sheets.status', $query))
            ->assertOk()
            ->assertJsonPath('completed', 1)
            ->assertJsonPath('slots.2:attendance.name', 'attendance.xlsx');

        $this->actingAs($recordsOfficer)
            ->get(route('school-forms.grade-sheets.template', ['type' => 'summary'] + $query + ['period' => 3]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function addEnrollment(string $schoolYear, string $level, array $grades): SchoolFormEnrollment
    {
        $student = SchoolFormStudent::where('student_number', '2020-0001')->sole();
        $enrollment = SchoolFormEnrollment::create([
            'student_id' => $student->id,
            'school_year' => $schoolYear,
            'level' => $level,
            'section' => 'Bambi',
            'adviser_name' => 'Test Adviser',
        ]);
        foreach ($grades as $period => $grade) {
            SchoolFormGrade::create([
                'student_enrollment_id' => $enrollment->id,
                'grading_period' => $period,
                'learning_area' => 'Mathematics',
                'grade' => $grade,
            ]);
        }

        return $enrollment;
    }

    private function assertPdfPageCount(string $contents, int $expected): void
    {
        $path = tempnam(sys_get_temp_dir(), 'f137-pdf-');
        file_put_contents($path, $contents);
        try {
            $this->assertSame($expected, (new Fpdi)->setSourceFile($path));
        } finally {
            @unlink($path);
        }
    }

    private function assertReadableF137Layout(string $workbook): void
    {
        $path = tempnam(sys_get_temp_dir(), 'f137-test-');
        file_put_contents($path, $workbook);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $styles = (string) $zip->getFromName('xl/styles.xml');
        $sharedStrings = (string) ($zip->getFromName('xl/sharedStrings.xml') ?: '');
        $zip->close();
        @unlink($path);

        foreach (['I9:K9', 'L9:P9', 'L24:M24', 'N24:O24', 'N53:O53'] as $merge) {
            $this->assertStringContainsString('ref="'.$merge.'"', $sheet);
        }
        $this->assertStringNotContainsString('ref="I9:P9"', $sheet);
        $this->assertStringNotContainsString('ref="L24:N24"', $sheet);
        $this->assertStringContainsString('<c r="L9"', $sheet);
        $this->assertStringContainsString('Juan', $sheet.$sharedStrings);
        $this->assertStringContainsString('<c r="G10"', $sheet);
        $this->assertStringContainsString('424413240015', $sheet.$sharedStrings);
        $this->assertStringContainsString('<b/>', $styles);
        $this->assertStringContainsString('<i/>', $styles);
    }

    private function configureSigningCertificate(): string
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'reghub-f138-signing-'.Str::uuid();
        mkdir($directory, 0700, true);
        $options = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'digest_alg' => 'sha256',
        ];
        $xamppConfig = 'C:\\xampp\\php\\extras\\ssl\\openssl.cnf';
        if (is_file($xamppConfig)) {
            $options['config'] = $xamppConfig;
        }

        $key = openssl_pkey_new($options);
        $csr = openssl_csr_new(['commonName' => 'RegHub F138 Test Signer'], $key, $options);
        $certificate = openssl_csr_sign($csr, null, $key, 7, $options);
        openssl_pkey_export($key, $privateKeyPem, 'test-password', $options);
        openssl_x509_export($certificate, $certificatePem);

        $certificatePath = $directory.DIRECTORY_SEPARATOR.'certificate.crt';
        $privateKeyPath = $directory.DIRECTORY_SEPARATOR.'private.key';
        file_put_contents($certificatePath, $certificatePem);
        file_put_contents($privateKeyPath, $privateKeyPem);
        config([
            'pdf_signing.certificate_path' => $certificatePath,
            'pdf_signing.private_key_path' => $privateKeyPath,
            'pdf_signing.private_key_password' => 'test-password',
            'pdf_signing.signer_name' => 'RegHub F138 Test Signer',
        ]);

        return $directory;
    }
}
