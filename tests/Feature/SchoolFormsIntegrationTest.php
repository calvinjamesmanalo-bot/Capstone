<?php

namespace Tests\Feature;

use App\Models\DocumentAuthenticity;
use App\Models\RequestDocument;
use App\Models\SchoolFormEnrollment;
use App\Models\SchoolFormGrade;
use App\Models\SchoolFormStudent;
use App\Models\SchoolFormUpload;
use App\Support\GradeSheetImporter;
use App\Support\XlsxWorkbookReader;
use App\Models\Student as RequestStudent;
use App\Models\User;
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
            ->assertSee('F137 Preview')
            ->assertSee('Mathematics');

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
                            ['column' => 'B', 'value' => 'Grade 2 - Amity'],
                            ['column' => 'C', 'value' => 'THIRD GRADING'],
                        ],
                    ]],
                ]];
            }
        };
        $importer = new GradeSheetImporter($reader);

        $importer->assertMatchesSelection('unused.xlsx', '2021-2022', 'Grade 2', 3);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Workbook metadata mismatch');
        $importer->assertMatchesSelection('unused.xlsx', '2020-2021', 'Grade 1', 3);
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
            ->post(route('school-forms.grade-sheets.store'), [
                'grade_school_year' => '2021-2022',
                'grade_level' => 'Grade 2',
                'grade_section' => 'Amity',
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
