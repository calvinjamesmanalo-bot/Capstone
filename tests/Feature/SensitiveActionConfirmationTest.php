<?php

namespace Tests\Feature;

use App\Models\DocumentAuthenticity;
use App\Models\Form138Upload;
use App\Models\RequestDocument;
use App\Models\SchoolFormUpload;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class SensitiveActionConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_status_forms_warn_with_the_ticket_number(): void
    {
        $staff = User::factory()->create(['role' => 'records_officer']);
        $requestDocument = $this->requestDocument('SAFE-TICKET-104', 'processing');

        $this->actingAs($staff)->get(route('requests.index'))
            ->assertOk()
            ->assertSee('data-request-identifier="SAFE-TICKET-104"', false)
            ->assertSee('Reject request ', false)
            ->assertSee(' as released?', false)
            ->assertSee('This cannot be undone through Request Management.');

        $requestDocument->update(['status' => 'processed']);

        $this->actingAs(User::factory()->create(['role' => 'registrar']))
            ->get(route('requests.index'))
            ->assertOk()
            ->assertSee(' as ready for release?', false)
            ->assertSee('Reject request ', false);
    }

    public function test_admin_clear_forms_warn_that_the_action_cannot_be_undone(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('logs.index'))
            ->assertOk()
            ->assertSee('Clear all activity logs and document verification audit logs?')
            ->assertSee('This cannot be undone.');

        $this->actingAs($admin)->get(route('requests.history'))
            ->assertOk()
            ->assertSee('Permanently delete ', false)
            ->assertSee('This cannot be undone.');
    }

    public function test_grade_upload_delete_warning_uses_original_filename_and_record_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Student::create([
            'student_number' => 'UPLOAD-100',
            'name' => 'Upload Test Student',
            'lrn' => '111111111111',
        ]);
        $upload = Form138Upload::create([
            'student_number' => 'UPLOAD-100',
            'school_year' => config('academics.school_years')[0],
            'file_path' => 'private/internal-generated-name.pdf',
            'original_filename' => 'report-card.pdf',
            'storage_disk' => 'local',
        ]);

        $this->actingAs($admin)
            ->get(route('grade-portal.index', ['search_student' => 'UPLOAD-100']))
            ->assertOk()
            ->assertSee('Delete grade upload ')
            ->assertSee('report-card.pdf')
            ->assertSee("(record #{$upload->id})?")
            ->assertSee('This cannot be undone.')
            ->assertDontSee('private/internal-generated-name.pdf');
    }

    public function test_grade_sheet_delete_warning_uses_the_original_filename(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $upload = new SchoolFormUpload([
            'school_year' => config('academics.school_years')[0],
            'level' => 'Grade 1',
            'section' => 'Bambi',
            'grading_period' => 1,
            'file_type' => 'attendance',
            'original_name' => 'attendance-q1.xlsx',
            'stored_path' => 'private/secret-location.xlsx',
        ]);
        $upload->id = 25;

        $this->view('school-forms.records', [
            'schoolYear' => $upload->school_year,
            'level' => $upload->level,
            'section' => $upload->section,
            'hasSearch' => true,
            'schoolYears' => collect([$upload->school_year]),
            'levels' => collect([$upload->level]),
            'sections' => collect([$upload->section]),
            'uploads' => collect([$upload]),
            'attendanceUploads' => collect([$upload]),
            'summaryUploads' => collect(),
            'errors' => new ViewErrorBag,
        ])->assertSee('Delete upload ')
            ->assertSee('attendance-q1.xlsx')
            ->assertSee('and all records imported from it?')
            ->assertSee('This cannot be undone.')
            ->assertDontSee('private/secret-location.xlsx');
    }

    public function test_revoke_warning_uses_the_public_control_number(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $document = new DocumentAuthenticity([
            'verification_token' => '00000000-0000-4000-8000-000000000001',
            'control_number' => 'DOC-SAFE-100',
            'document_type' => 'Certificate of Enrollment',
            'holder_name' => 'Test Holder',
            'issued_at' => now(),
            'status' => 'valid',
            'pdf_signature_status' => 'signed',
            'blockchain_status' => 'unavailable',
        ]);
        $document->id = 44;

        $this->view('documents.issued', compact('document'))
            ->assertSee('Revoke issued document DOC-SAFE-100?')
            ->assertSee('verification result will immediately show as revoked')
            ->assertSee('This cannot be undone; a correction requires a new document ID.');
    }

    public function test_sensitive_forms_keep_csrf_and_non_get_methods(): void
    {
        foreach ([
            'resources/views/logs/index.blade.php',
            'resources/views/requests/history.blade.php',
            'resources/views/requests/index.blade.php',
            'resources/views/grade-portal/index.blade.php',
            'resources/views/school-forms/records.blade.php',
            'resources/views/documents/issued.blade.php',
        ] as $view) {
            $contents = file_get_contents(base_path($view));

            $this->assertStringContainsString('@csrf', $contents, $view);
            $this->assertStringNotContainsString('method="GET" action="{{ route(\'logs.clear\')', $contents, $view);
            $this->assertStringNotContainsString('method="GET" action="{{ route(\'documents.revoke\')', $contents, $view);
        }
    }

    private function requestDocument(string $ticket, string $status): RequestDocument
    {
        Student::create([
            'student_number' => 'REQUEST-100',
            'name' => 'Request Test Student',
            'lrn' => '222222222222',
        ]);

        return RequestDocument::create([
            'ticket_number' => $ticket,
            'student_number' => 'REQUEST-100',
            'document_type' => 'Certificate of Enrollment',
            'status' => $status,
            'clearance_status' => 'cleared',
        ]);
    }
}
