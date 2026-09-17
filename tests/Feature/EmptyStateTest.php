<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class EmptyStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_distinguish_empty_lists_from_filter_matches(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'records_officer']));
        $this->get(route('requests.index'))->assertOk()->assertSee('No active requests')
            ->assertSee('View request history')->assertDontSee('No matching requests found');
        foreach ([['search' => 'missing'], ['status' => 'processing']] as $parameters) {
            $this->get(route('requests.index', $parameters))->assertOk()
                ->assertSee('No matching requests found')->assertSee('Clear search and filters');
        }
        $this->get(route('requests.history'))->assertOk()
            ->assertSee('No request history yet')->assertSee('View active requests');
    }

    public function test_student_empty_sections_offer_a_new_request(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'student']));
        $this->view('requests.my-requests', ['activeRequests' => collect(), 'requestHistory' => collect()])
            ->assertSee('No active requests')->assertSee('No request history yet')
            ->assertSee('Submit a new request')->assertSee('role="status"', false);
    }

    public function test_logs_explain_empty_activity_and_verification_sections(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->view('logs.index', [
            'logs' => new LengthAwarePaginator([], 0, 10),
            'verificationAudits' => new LengthAwarePaginator([], 0, 10),
        ])->assertSee('No activity recorded yet')->assertSee('No document verifications recorded')
            ->assertDontSee('No matching');
    }

    public function test_grade_sheet_initial_and_filtered_empty_states(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'records_officer']));
        $data = [
            'schoolYear' => '', 'level' => '', 'section' => '', 'hasSearch' => false,
            'schoolYears' => collect(config('academics.school_years')), 'levels' => collect(),
            'sections' => collect(), 'uploads' => collect(), 'attendanceUploads' => collect(),
            'summaryUploads' => collect(), 'errors' => new ViewErrorBag,
        ];
        $this->view('school-forms.records', $data)->assertSee('No grade sheets uploaded yet')
            ->assertSee('href="#grade-sheet-upload"', false)->assertSee('Upload grade sheets');
        $this->view('school-forms.records', [...$data, 'levels' => collect(['Grade 1'])])
            ->assertSee('Choose a class to view its records')->assertDontSee('No grade sheets uploaded yet');
        $this->view('school-forms.records', [...$data, 'hasSearch' => true,
            'schoolYear' => '2025-2026', 'level' => 'Grade 1', 'section' => 'Bambi'])
            ->assertSee('No matching attendance sheets')->assertSee('No matching summary sheets')
            ->assertSee('Clear class filters')->assertDontSee('No grade sheets uploaded yet');
    }

    public function test_legacy_grade_portal_distinguishes_no_search_no_student_and_no_uploads(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $this->get(route('grade-portal.index'))->assertOk()->assertSee('Find a student’s grade records');
        $this->get(route('grade-portal.index', ['search_student' => 'missing']))->assertOk()
            ->assertSee('No matching student found')->assertSee('Clear student search');
        Student::create(['student_number' => 'EMPTY-1', 'name' => 'Empty Student', 'lrn' => '123456789012']);
        $this->get(route('grade-portal.index', ['search_student' => 'EMPTY-1']))->assertOk()
            ->assertSee('No grade records for this student')->assertSee('Upload a grade record');
        $this->actingAs(User::factory()->create(['role' => 'records_officer']))
            ->get(route('grade-portal.index'))->assertForbidden();
    }

    public function test_component_escapes_content_and_omits_unavailable_actions(): void
    {
        $value = '<script>alert(1)</script>';
        $this->blade('<x-empty-state :heading="$value" :description="$value" action-label="Restricted action" />', compact('value'))
            ->assertSee($value)->assertDontSee($value, false)->assertDontSee('Restricted action')
            ->assertSee('role="status"', false);
    }
}
