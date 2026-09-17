<?php

namespace Tests\Feature;

use App\Models\RequestDocument;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResponsiveInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_layout_has_responsive_navigation_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-mobile-sidebar', false)
            ->assertSee('data-mobile-sidebar-toggle', false)
            ->assertSee('data-mobile-sidebar-close', false)
            ->assertSee('data-mobile-sidebar-backdrop', false)
            ->assertSee('lg:ml-64', false)
            ->assertSee('page-content p-4 sm:p-6 lg:p-8', false)
            ->assertSee('.request-a11y :is(a, button, input, select, textarea, [tabindex="0"]):focus-visible', false);
    }

    public function test_public_account_creation_page_uses_the_mobile_foundation(): void
    {
        $this->get(route('student.registration.request'))
            ->assertOk()
            ->assertSee('min-height: 100dvh', false)
            ->assertSee('p-6 shadow-lg sm:p-8', false);
    }

    public function test_request_management_keeps_all_columns_in_a_keyboard_scrollable_region(): void
    {
        $request = $this->documentRequest();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('requests.index'))
            ->assertOk()
            ->assertSee('role="region" aria-label="Request management table"', false)
            ->assertSee('tabindex="0"', false)
            ->assertSee('Scroll sideways to view every request detail and action.')
            ->assertSee('Ticket and student')
            ->assertSee('Current status')
            ->assertSee('Actions')
            ->assertSee('for="request-status-'.$request->id.'"', false)
            ->assertSee('for="status-remarks-'.$request->id.'"', false)
            ->assertSee('for="clearance-status-'.$request->id.'"', false)
            ->assertSee('for="financial-balance-'.$request->id.'"', false)
            ->assertSee('aria-label="Update status for request', false)
            ->assertSee('aria-label="Open certification maker for request', false);
    }

    public function test_student_request_pages_have_labeled_choices_and_mobile_controls(): void
    {
        $student = $this->studentUser();
        $this->documentRequest();

        $this->actingAs($student)->get(route('student.request'))
            ->assertOk()
            ->assertSee('aria-label="Step 1: Choose document"', false)
            ->assertSee('for="school_year"', false)
            ->assertSee('for="transcript_receipt"', false)
            ->assertSee('request-a11y', false);

        $this->get(route('student.my-requests'))
            ->assertOk()
            ->assertSee('request-a11y', false)
            ->assertSee('min-w-0 p-5 sm:p-10', false)
            ->assertSee('Pending')
            ->assertSee('View Transcript Receipt');
    }

    public function test_receipt_and_dashboard_have_mobile_actions_and_accessible_status(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('transcript_receipts/test.pdf', 'receipt');
        $request = $this->documentRequest();

        $this->actingAs($this->studentUser())->get(route('requests.receipt', $request))
            ->assertOk()
            ->assertSee('request-a11y', false)
            ->assertSee('w-full rounded-xl', false)
            ->assertSee('Print Receipt')
            ->assertSee('Request Status')
            ->assertSee('Pending');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('role="region" aria-label="Active requests table"', false)
            ->assertSee('role="region" aria-label="Request history table"', false)
            ->assertSee('Scroll sideways to view all request details.')
            ->assertSee('focus-visible:ring-4', false);
    }

    private function studentUser(): User
    {
        $student = Student::firstOrCreate(['student_number' => 'RESPONSIVE-100'], [
            'name' => 'Responsive Student',
            'official_email' => 'responsive.student@example.test',
        ]);

        return User::factory()->create([
            'role' => 'student',
            'student_number' => $student->student_number,
            'email' => $student->official_email,
            'email_verified_at' => now(),
        ]);
    }

    private function documentRequest(): RequestDocument
    {
        Student::firstOrCreate(['student_number' => 'RESPONSIVE-100'], [
            'name' => 'Responsive Student',
            'official_email' => 'responsive.student@example.test',
        ]);

        return RequestDocument::create([
            'ticket_number' => 'RESPONSIVE-TICKET',
            'student_number' => 'RESPONSIVE-100',
            'document_type' => 'Certificate of Enrollment',
            'status' => 'pending',
            'clearance_status' => 'cleared',
            'payment_proof_path' => 'transcript_receipts/test.pdf',
            'payment_proof_disk' => 'local',
        ]);
    }
}
