<?php

namespace Tests\Feature;

use App\Models\RequestDocument;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_new_requests_are_cash_only(): void
    {
        Storage::fake('local');
        $student = $this->student('2026-CASH-ONLY');
        $payload = $this->basePayload('Certificate of Enrollment');
        $payload['payment_method'] = 'gcash';

        $this->actingAs($student)->post(route('student.request.store'), $payload)
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('request_documents', 0);
    }

    public function test_f137_transfer_requires_school_id_proof_but_not_payment_choice(): void
    {
        Storage::fake('local');
        $student = $this->student('2026-F137-ASSESS');

        $payload = [
            'document_type' => 'Form 137',
            'school_level' => 'jhs',
            'request_purpose' => 'transfer_enrollment',
            'purpose_details' => 'Transfer enrollment',
            'receiving_school_name' => 'Receiving School',
            'receiving_address_scope' => 'domestic',
            'receiving_school_address' => '123 School Street, Barangay Sample, Cavite City, Cavite, 4100',
            'request_proof' => UploadedFile::fake()->create('school-request.pdf', 50, 'application/pdf'),
            'delivery_method' => 'school_to_school',
        ];

        $this->actingAs($student)->post(route('student.request.store'), $payload)
            ->assertSessionHasErrors('school_id_proof');

        $payload['school_id_proof'] = UploadedFile::fake()->image('school-id.png');
        $this->actingAs($student)->post(route('student.request.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('request_documents', [
            'student_number' => $student->student_number,
            'document_type' => 'Form 137',
            'delivery_method' => 'school_to_school',
            'payment_method' => 'cash',
            'payment_confirmed' => false,
        ]);
    }

    public function test_form_138_and_diploma_are_pickup_only_even_for_forged_submissions(): void
    {
        Storage::fake('local');
        $student = $this->student('2026-PICKUP');

        foreach (['Form 138', 'Diploma'] as $documentType) {
            $payload = $this->basePayload($documentType, 'delivery');
            if ($documentType === 'Form 138') {
                $payload['school_year'] = config('academics.school_years')[0];
            }

            $this->actingAs($student)->post(route('student.request.store'), $payload)
                ->assertSessionHasErrors('delivery_method');
        }

        $this->assertDatabaseCount('request_documents', 0);
    }

    public function test_non_f137_requests_ignore_the_hidden_f137_purpose_selection(): void
    {
        Storage::fake('local');
        $student = $this->student('2026-NON-F137');

        foreach (['Form 138', 'Diploma', 'Certificate of Enrollment'] as $documentType) {
            $payload = $this->basePayload($documentType) + [
                'request_purpose' => 'not-a-real-purpose',
                'school_level' => 'not-a-real-level',
                'purpose_details' => str_repeat('x', 1200),
                'receiving_address_scope' => 'not-a-real-scope',
                'receiving_school_address' => str_repeat('x', 1200),
                'employer_name' => str_repeat('x', 200),
            ];
            if ($documentType === 'Form 138') {
                $payload['school_year'] = config('academics.school_years')[0];
            }

            $this->actingAs($student)->post(route('student.request.store'), $payload)
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('request_documents', 3);
    }

    public function test_active_form_137_limits_are_separate_for_each_level_and_employment(): void
    {
        Storage::fake('local');
        $student = $this->student('2026-F137-LIMITS');
        RequestDocument::create([
            'student_number' => $student->student_number,
            'document_type' => 'Form 137',
            'school_level' => 'kinder',
            'request_purpose' => 'transfer_enrollment',
            'status' => 'pending',
        ]);

        $elementary = $this->basePayload('Form 137', 'school_to_school') + [
            'school_level' => 'elementary',
            'request_purpose' => 'transfer_enrollment',
            'purpose_details' => 'Transfer',
            'receiving_school_name' => 'Receiving School',
            'receiving_address_scope' => 'domestic',
            'receiving_school_address' => '123 School Street, Cavite',
            'request_proof' => UploadedFile::fake()->create('request.pdf', 50, 'application/pdf'),
        ];
        $this->actingAs($student)->post(route('student.request.store'), $elementary)
            ->assertSessionHas('error');

        $jhs = $elementary;
        $jhs['school_level'] = 'jhs';
        $jhs['transcript_receipt'] = UploadedFile::fake()->image('jhs-receipt.png');
        $jhs['request_proof'] = UploadedFile::fake()->create('jhs-request.pdf', 50, 'application/pdf');
        $this->actingAs($student)->post(route('student.request.store'), $jhs)
            ->assertSessionHasNoErrors();

        $employment = $this->basePayload('Form 137') + [
            'school_level' => 'elementary',
            'request_purpose' => 'employment',
            'employer_name' => 'Example Employer',
            'employment_proof' => UploadedFile::fake()->create('employment.pdf', 50, 'application/pdf'),
        ];
        $this->actingAs($student)->post(route('student.request.store'), $employment)
            ->assertSessionHasNoErrors();

        $employment['transcript_receipt'] = UploadedFile::fake()->image('second-employment-receipt.png');
        $employment['employment_proof'] = UploadedFile::fake()->create('second-employment.pdf', 50, 'application/pdf');
        $this->actingAs($student)->post(route('student.request.store'), $employment)
            ->assertSessionHas('error');

        $this->assertDatabaseCount('request_documents', 3);
    }

    public function test_request_page_removes_shipping_note_and_exposes_active_limit_to_first_step(): void
    {
        $student = $this->student('2026-FIRST-STEP');
        RequestDocument::create([
            'student_number' => $student->student_number,
            'document_type' => 'Diploma',
            'status' => 'pending',
        ]);

        $this->actingAs($student)->get(route('student.request'))
            ->assertOk()
            ->assertDontSee('Shipping fee is temporarily disabled')
            ->assertDontSee('Please check the information below.')
            ->assertSee('document:diploma')
            ->assertSee('active_request_limit_error');
    }

    public function test_released_form_137_routes_repeat_transfer_and_employment_requests_for_review(): void
    {
        Storage::fake('local');
        $student = $this->student('2026-F137');
        RequestDocument::create([
            'student_number' => $student->student_number,
            'document_type' => 'Form 137',
            'school_level' => 'shs',
            'request_purpose' => 'transfer_enrollment',
            'receiving_school_name' => 'Receiving School',
            'status' => 'completed',
        ]);

        $transfer = $this->basePayload('Form 137', 'school_to_school');
        $transfer['school_level'] = 'shs';
        $transfer['request_purpose'] = 'transfer_enrollment';
        $transfer['purpose_details'] = 'Enrollment transfer';
        $transfer['receiving_school_name'] = 'Receiving School';
        $transfer['receiving_address_scope'] = 'domestic';
        $transfer['receiving_school_street'] = '123 School St.';
        $transfer['receiving_barangay'] = 'San Jose';
        $transfer['receiving_city'] = 'City of Dasmariñas';
        $transfer['receiving_province'] = 'Cavite';
        $transfer['receiving_region_code'] = '0400000000';
        $transfer['request_proof'] = UploadedFile::fake()->create('school-request.pdf', 100, 'application/pdf');
        $this->actingAs($student)->post(route('student.request.store'), $transfer)
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('request_documents', [
            'student_number' => $student->student_number,
            'request_purpose' => 'transfer_enrollment',
            'review_status' => 'pending',
            'delivery_method' => 'school_to_school',
            'lbc_sakto_rate' => null,
        ]);

        $employment = $this->basePayload('Form 137');
        $employment['school_level'] = 'shs';
        $employment['request_purpose'] = 'employment';
        $employment['employer_name'] = 'Example Employer';
        $employment['employment_proof'] = UploadedFile::fake()->create('job-requirement.pdf', 100, 'application/pdf');
        RequestDocument::where('student_number', $student->student_number)->where('status', 'pending')->update(['status' => 'completed']);
        $this->post(route('student.request.store'), $employment)->assertSessionHasNoErrors();

        $exception = RequestDocument::latest('id')->firstOrFail();
        $this->assertTrue($exception->is_f137_employment_exception);
        $this->assertSame('pending', $exception->review_status);
        $this->assertSame('Example Employer', $exception->employer_name);
        $this->assertNotNull($exception->employment_proof_path);
        $this->assertNull($exception->exception_approved_at);
    }

    public function test_employment_exception_requires_registrar_or_admin_approval_before_processing(): void
    {
        $student = $this->student('2026-APPROVAL');
        $request = RequestDocument::create([
            'student_number' => $student->student_number,
            'document_type' => 'Form 137',
            'school_level' => 'shs',
            'request_purpose' => 'employment',
            'employer_name' => 'Example Employer',
            'is_f137_employment_exception' => true,
            'status' => 'pending',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'records_officer']))
            ->post(route('requests.update-status', $request), ['status' => 'processing'])
            ->assertSessionHasErrors('status');

        $registrar = User::factory()->create(['role' => 'registrar']);
        $this->actingAs($registrar)
            ->post(route('requests.update-status', $request), ['status' => 'processing'])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('processing', $request->status);
        $this->assertNotNull($request->exception_approved_at);
        $this->assertSame($registrar->id, $request->exception_approved_by);
    }

    public function test_f137_transmission_and_f138_claim_are_recorded_with_required_evidence(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['role' => 'records_officer']);
        $student = $this->student('2026-FULFILL');
        $f137 = RequestDocument::create([
            'student_number' => $student->student_number,
            'document_type' => 'Form 137',
            'request_purpose' => 'transfer_enrollment',
            'receiving_school_name' => 'New School',
            'receiving_school_address' => 'New School Address',
            'delivery_method' => 'school_to_school',
            'document_price' => 175,
            'f137_delivery_method' => 'lbc',
            'delivery_fee' => 158,
            'delivery_assessed_at' => now(),
            'delivery_assessed_by' => $staff->id,
            'payment_confirmed' => true,
            'payment_confirmed_at' => now(),
            'payment_confirmed_by' => $staff->id,
            'payment_amount' => 333,
            'status' => 'ready_for_transmission',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'registrar']))
            ->post(route('requests.update-status', $f137), [
                'status' => 'transmitted',
                'transmission_method' => 'lbc',
                'recipient_name' => 'Registrar Office',
                'transmission_destination' => 'registrar@new-school.test',
                'reference_number' => 'TX-FORGED',
                'transmission_proof' => UploadedFile::fake()->create('sent-email.pdf', 50, 'application/pdf'),
            ])->assertSessionHasErrors('status');

        $this->actingAs($staff)->post(route('requests.update-status', $f137), [
            'status' => 'transmitted',
            'transmission_method' => 'courier',
            'recipient_name' => 'Registrar Office',
            'transmission_destination' => 'New School Address',
            'reference_number' => 'OTHER-COURIER',
            'transmission_proof' => UploadedFile::fake()->create('other-courier.pdf', 50, 'application/pdf'),
        ])->assertSessionHasErrors('transmission_method');

        $this->actingAs($staff)->post(route('requests.update-status', $f137), [
            'status' => 'transmitted',
            'transmission_method' => 'lbc',
            'recipient_name' => 'Registrar Office',
            'transmission_destination' => 'registrar@new-school.test',
            'reference_number' => 'TX-1001',
            'transmission_proof' => UploadedFile::fake()->create('sent-email.pdf', 50, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('request_document_transmissions', ['request_document_id' => $f137->id, 'reference_number' => 'TX-1001']);
        $this->assertSame('transmitted', $f137->fresh()->status);

        $this->actingAs($student)->get(route('student.my-requests'))
            ->assertOk()
            ->assertSee('School-to-school transmission')
            ->assertSee('Sent to receiving school')
            ->assertSee('registrar@new-school.test')
            ->assertSee('TX-1001')
            ->assertSee('Copy Reference &amp; Track on LBC', false)
            ->assertSee('https://www.lbcexpress.com/ph/track', false)
            ->assertDontSee('sent-email.pdf');

        $f138 = RequestDocument::create([
            'student_number' => $student->student_number,
            'document_type' => 'Form 138',
            'delivery_method' => 'pickup',
            'status' => 'ready_for_pickup',
        ]);
        $this->actingAs($staff)->get(route('requests.index'))
            ->assertOk()
            ->assertSee('Mark as Claimed')
            ->assertDontSee('Claimant full name')
            ->assertDontSee('name="claimant_name"', false);
        $this->actingAs($staff)
            ->post(route('requests.update-status', $f138), [
                'status' => 'claimed',
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('request_document_claims', ['request_document_id' => $f138->id, 'valid_id_last_four' => '----']);
        $this->assertSame('claimed', $f138->fresh()->status);
    }

    public function test_records_officer_assesses_f137_fee_before_cash_payment_and_staff_delivery(): void
    {
        $staff = User::factory()->create(['role' => 'records_officer']);
        $student = $this->student('2026-ASSESS');
        User::factory()->create(['role' => 'student', 'student_number' => $student->student_number]);
        $f137 = RequestDocument::create([
            'ticket_number' => 'REQ-2026-ASSESS',
            'student_number' => $student->student_number,
            'document_type' => 'Form 137',
            'request_purpose' => 'transfer_enrollment',
            'receiving_school_name' => 'Nearby School',
            'receiving_school_address' => 'Cavite',
            'document_price' => 175,
            'delivery_method' => 'school_to_school',
            'status' => 'ready_for_transmission',
        ]);

        $this->actingAs($staff)->post(route('requests.assess-f137-delivery', $f137), [
            'f137_delivery_method' => 'staff_delivery',
            'delivery_fee' => 25,
        ])->assertRedirect(route('requests.index', ['open_request' => $f137->id]));

        $f137->refresh();
        $this->assertSame('staff_delivery', $f137->f137_delivery_method);
        $this->assertSame('25.00', $f137->delivery_fee);

        $this->actingAs($staff)->post(route('requests.confirm-payment', $f137), [
            'payment_amount' => 200,
            'official_receipt_number' => 'OR-1001',
        ])->assertSessionHasNoErrors();

        $this->actingAs($staff)->post(route('requests.update-status', $f137), [
            'status' => 'transmitted',
            'transmission_method' => 'staff_delivery',
            'recipient_name' => 'Receiving Registrar',
            'transmission_destination' => 'Nearby School, Cavite',
        ])->assertSessionHasNoErrors();

        $f137->refresh();
        $this->assertTrue($f137->payment_confirmed);
        $this->assertSame('200.00', $f137->payment_amount);
        $this->assertSame('OR-1001', $f137->official_receipt_number);
        $this->assertSame('transmitted', $f137->status);
        $this->assertDatabaseHas('request_document_transmissions', ['request_document_id' => $f137->id, 'method' => 'staff_delivery']);
    }

    public function test_records_officer_can_audit_manual_f137_preparation_when_system_data_is_incomplete(): void
    {
        $student = $this->student('2026-MANUAL');
        $officer = User::factory()->create(['role' => 'records_officer']);
        $request = RequestDocument::create([
            'student_number' => $student->student_number,
            'document_type' => 'Form 137',
            'request_purpose' => 'transfer_enrollment',
            'status' => 'pending',
        ]);

        $this->actingAs($officer)->get(route('requests.index'))
            ->assertOk()
            ->assertSee('Manual Form 137 procedure')
            ->assertSee('Use Manual Procedure &amp; Forward to Registrar', false);

        $this->actingAs($officer)->post(route('requests.update-status', $request), [
            'status' => 'processed',
            'manual_preparation' => '1',
            'manual_preparation_reason' => 'Historical grades were unavailable in the imported school-form records.',
            'remarks' => 'Prepared from the verified physical student record.',
        ])->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertSame('processed', $request->status);
        $this->assertTrue($request->is_manually_prepared);
        $this->assertSame($officer->id, $request->manually_prepared_by);
        $this->assertNotNull($request->manually_prepared_at);
        $this->assertSame(3, $request->statusHistories()->count());
    }

    public function test_manual_preparation_is_available_for_built_in_documents_but_not_diploma(): void
    {
        $student = $this->student('2026-MANUAL-ALL');
        $officer = User::factory()->create(['role' => 'records_officer']);
        $certificate = RequestDocument::create([
            'student_number' => $student->student_number,
            'document_type' => 'Certificate of Enrollment',
            'status' => 'pending',
        ]);
        RequestDocument::create([
            'student_number' => $student->student_number,
            'document_type' => 'Diploma',
            'status' => 'pending',
        ]);

        $this->actingAs($officer)->get(route('requests.index'))
            ->assertOk()
            ->assertSee('Manual Certificate of Enrollment procedure')
            ->assertDontSee('Manual Diploma procedure');

        $this->actingAs($officer)->post(route('requests.update-status', $certificate), [
            'status' => 'processed',
            'manual_preparation' => '1',
            'manual_preparation_reason' => 'The historical source record is not retrievable by the system.',
            'remarks' => 'Prepared and verified from the physical registrar file.',
        ])->assertRedirect(route('requests.index', ['open_request' => $certificate->id]));

        $this->assertTrue($certificate->fresh()->is_manually_prepared);
        $this->assertTrue($certificate->fresh()->hasPreparedDocument());
    }

    public function test_overseas_f137_uses_separate_full_address_without_domestic_sakto_rate(): void
    {
        Storage::fake('local');
        $student = $this->student('2026-OVERSEAS');
        $payload = $this->basePayload('Form 137', 'school_to_school') + [
            'school_level' => 'shs',
            'request_purpose' => 'transfer_enrollment',
            'purpose_details' => 'Enrollment overseas',
            'receiving_school_name' => 'International Academy',
            'receiving_address_scope' => 'overseas',
            'receiving_overseas_address' => '100 Example Road, Toronto, Ontario A1A 1A1, Canada',
            'request_proof' => UploadedFile::fake()->create('school-request.pdf', 50, 'application/pdf'),
        ];

        $this->actingAs($student)->post(route('student.request.store'), $payload)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('request_documents', [
            'student_number' => $student->student_number,
            'receiving_address_scope' => 'overseas',
            'receiving_school_address' => '100 Example Road, Toronto, Ontario A1A 1A1, Canada',
            'lbc_sakto_rate' => null,
        ]);
    }

    private function student(string $number): User
    {
        Student::create(['student_number' => $number, 'name' => 'Policy Student', 'official_email' => strtolower($number).'@example.test']);

        return User::factory()->create(['role' => 'student', 'student_number' => $number, 'email' => strtolower($number).'@example.test']);
    }

    private function basePayload(string $documentType, string $delivery = 'pickup'): array
    {
        return [
            'document_type' => $documentType,
            'delivery_method' => $delivery,
            'release_location' => $delivery === 'delivery' ? 'Forged home address' : null,
            'payment_method' => 'cash',
            'transcript_receipt' => UploadedFile::fake()->image('receipt.png'),
        ];
    }
}
