<?php

namespace Tests\Feature;

use App\Models\RequestDocument;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestManagementSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_search_active_requests_by_supported_student_and_request_fields(): void
    {
        $staff = User::factory()->create(['role' => 'records_officer']);

        $ticketMatch = $this->documentRequest('STU-1001', 'Alpha Student', '100000000001', 'TICKET-ALPHA');
        $nameMatch = $this->documentRequest('STU-1002', 'Bravo Searchable', '100000000002', 'TICKET-BRAVO');
        $numberMatch = $this->documentRequest('STU-UNIQUE-1003', 'Charlie Student', '100000000003', 'TICKET-CHARLIE');
        $lrnMatch = $this->documentRequest('STU-1004', 'Delta Student', '987654321004', 'TICKET-DELTA');
        $unrelated = $this->documentRequest('STU-1005', 'Unrelated Student', '100000000005', 'TICKET-OTHER');

        foreach ([
            '  ticket-alpha  ' => $ticketMatch,
            'SEARCHABLE' => $nameMatch,
            'unique-1003' => $numberMatch,
            '987654321004' => $lrnMatch,
        ] as $search => $match) {
            $response = $this->actingAs($staff)->get(route('requests.index', ['search' => $search]));

            $response->assertOk()
                ->assertSee($match->student->name)
                ->assertDontSee($unrelated->student->name);
        }
    }

    public function test_search_is_preserved_during_pagination(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);

        foreach (range(1, 16) as $index) {
            $this->documentRequest(
                sprintf('PAGE-%04d', $index),
                sprintf('Paginated Student %02d', $index),
                sprintf('20000000%04d', $index),
                sprintf('SHARED-%04d', $index),
            );
        }

        $this->actingAs($staff)
            ->get(route('requests.index', ['search' => 'shared']))
            ->assertOk()
            ->assertSee('search=shared', false);
    }

    public function test_request_search_keeps_existing_staff_authorization(): void
    {
        $this->get(route('requests.index', ['search' => 'student']))
            ->assertRedirect(route('login'));

        $student = $this->documentRequest(
            'AUTH-1001',
            'Authorization Student',
            '300000000001',
            'AUTH-TICKET',
        )->student;
        $studentUser = User::factory()->create([
            'role' => 'student',
            'student_number' => $student->student_number,
            'email' => 'authorization.student@example.test',
            'email_verified_at' => now(),
        ]);
        $student->update(['official_email' => $studentUser->email]);

        $this->actingAs($studentUser)
            ->get(route('requests.index', ['search' => 'AUTH-TICKET']))
            ->assertForbidden();

        foreach (['admin', 'registrar', 'records_officer'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('requests.index', ['search' => 'AUTH-TICKET']))
                ->assertOk();
        }
    }

    public function test_search_displays_a_specific_empty_state_when_nothing_matches(): void
    {
        $staff = User::factory()->create(['role' => 'records_officer']);
        $this->documentRequest('EMPTY-1001', 'Existing Student', '400000000001', 'EXISTING-TICKET');

        $this->actingAs($staff)
            ->get(route('requests.index', ['search' => 'no-such-request']))
            ->assertOk()
            ->assertSee('No matching requests found')
            ->assertSee('value="no-such-request"', false);
    }

    public function test_each_filter_limits_results_to_matching_requests(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'records_officer']));
        $match = $this->documentRequest('FILTER-1', 'Matching Student', '500000000001', 'FILTER-MATCH');
        $other = $this->documentRequest('FILTER-2', 'Other Student', '500000000002', 'FILTER-OTHER');
        $match->update($this->filterValues());
        $other->update([
            'status' => 'pending', 'document_type' => 'Form 137',
            'payment_method' => 'cash', 'delivery_method' => 'pickup',
            'school_year' => config('academics.school_years')[1],
        ]);

        foreach ($this->filterValues() as $key => $value) {
            $this->get(route('requests.index', [$key => $value]))
                ->assertOk()
                ->assertViewHas('requests', fn ($requests) => $requests->pluck('id')->all() === [$match->id])
                ->assertSee('value="'.$value.'" selected', false)
                ->assertSee('Active filters:')
                ->assertSee('Apply Filters')
                ->assertSee('Clear Filters');
        }
    }

    public function test_filters_combine_with_search_and_survive_pagination(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $parameters = ['search' => 'shared', ...$this->filterValues()];
        foreach (range(1, 16) as $index) {
            $this->documentRequest('COMBINED-'.$index, 'Combined Student '.$index,
                sprintf('60000000%04d', $index), 'SHARED-'.$index)->update($this->filterValues());
        }
        // Each near match differs in just one filter, exercising AND grouping.
        foreach (['status' => 'pending', 'document_type' => 'Form 137',
            'payment_method' => 'cash', 'delivery_method' => 'pickup',
            'school_year' => config('academics.school_years')[1]] as $key => $value) {
            $this->documentRequest('MISS-'.$key, 'Shared Near Match '.$key,
                '70000000000'.count(RequestDocument::all()), 'SHARED-MISS-'.$key)
                ->update([...$this->filterValues(), $key => $value]);
        }
        $this->documentRequest('NO-SEARCH', 'Unrelated Student', '800000000001', 'UNRELATED')
            ->update($this->filterValues());

        $response = $this->get(route('requests.index', $parameters))->assertOk();
        $paginator = $response->viewData('requests');
        $this->assertSame(16, $paginator->total());
        parse_str(parse_url($paginator->nextPageUrl(), PHP_URL_QUERY), $nextParameters);
        $this->assertSame([...$parameters, 'page' => '2'], $nextParameters);
        $this->get($paginator->nextPageUrl())->assertOk()
            ->assertViewHas('requests', fn ($requests) => $requests->count() === 1 && $requests->total() === 16);
    }

    public function test_invalid_and_array_filters_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ($this->filterValues() as $key => $value) {
            foreach (['unsupported-value', [$value]] as $invalid) {
                $this->getJson(route('requests.index', [$key => $invalid]))
                    ->assertUnprocessable()->assertJsonValidationErrors($key);
            }
        }
        $this->getJson(route('requests.index', ['status' => 'for_approval']))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_filters_cannot_expand_existing_role_visibility(): void
    {
        $document = $this->documentRequest('SCOPE-1', 'Scope Student', '900000000001', 'SCOPE');
        foreach (['admin', 'records_officer', 'registrar'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            foreach (['completed', 'rejected'] as $status) {
                $document->update(['status' => $status]);
                $this->get(route('requests.index', ['status' => $status]))->assertOk()
                    ->assertViewHas('requests', fn ($requests) => $requests->isEmpty())
                    ->assertSee('No matching requests found');
            }
        }
        $document->update(['status' => 'pending']);
        $this->get(route('requests.index', ['status' => 'pending']))->assertOk()
            ->assertViewHas('requests', fn ($requests) => $requests->isEmpty());
        $document->update(['status' => 'processed']);
        $this->get(route('requests.index', ['status' => 'processed']))->assertOk()
            ->assertViewHas('requests', fn ($requests) => $requests->total() === 1);
    }

    public function test_empty_filters_leave_the_listing_unfiltered(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->documentRequest('EMPTY-FILTER', 'Visible Student', '910000000001', 'VISIBLE');
        $this->get(route('requests.index', array_fill_keys(array_keys($this->filterValues()), '')))
            ->assertOk()->assertViewHas('activeParameters', [])
            ->assertViewHas('requests', fn ($requests) => $requests->total() === 1)
            ->assertDontSee('Active filters:');
    }

    private function filterValues(): array
    {
        return [
            'status' => 'processing',
            'document_type' => 'Form 138',
            'payment_method' => 'bank_transfer',
            'delivery_method' => 'delivery',
            'school_year' => config('academics.school_years')[0],
        ];
    }

    private function documentRequest(
        string $studentNumber,
        string $studentName,
        string $lrn,
        string $ticketNumber,
    ): RequestDocument {
        Student::create([
            'student_number' => $studentNumber,
            'lrn' => $lrn,
            'name' => $studentName,
        ]);

        return RequestDocument::create([
            'ticket_number' => $ticketNumber,
            'student_number' => $studentNumber,
            'document_type' => 'Certificate of Enrollment',
            'status' => 'pending',
            'clearance_status' => 'cleared',
        ])->load('student');
    }
}
