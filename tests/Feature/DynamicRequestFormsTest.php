<?php

namespace Tests\Feature;

use App\Models\RequestDocument;
use App\Models\RequestType;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DynamicRequestFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_registrar_can_create_edit_disable_enable_and_archive_using_only_name_and_price(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $this->get(route('request-types.index'))->assertOk()
            ->assertSee('Request/Form Name')->assertSee('Price (PHP)')
            ->assertDontSee('Student fields')->assertDontSee('Add field')->assertDontSee('Dropdown options')
            ->assertDontSee('name="is_active"', false)->assertDontSee('name="description"', false);
        $this->post(route('request-types.store'), ['name' => 'Custom transfer', 'fee' => 35.50])->assertSessionHasNoErrors();
        $type = RequestType::firstOrFail();
        $this->assertTrue($type->is_active);
        $this->assertSame([], $type->fields);
        $this->get(route('request-types.index'))->assertOk()->assertSee($type->name)->assertSee('35.50');
        $this->assertFalse(Route::has('request-types.edit'));
        $this->put(route('request-types.update', $type), ['name' => 'Transfer copy', 'fee' => 0, 'version' => 1])->assertSessionHasNoErrors();
        $this->assertSame('Transfer copy', $type->fresh()->name);
        $this->assertSame('0.00', $type->fresh()->fee);
        $this->put(route('request-types.update', $type), ['name' => 'Stale edit', 'fee' => 5, 'version' => 1])->assertSessionHasErrors('version');
        $this->patch(route('request-types.toggle', $type))->assertRedirect();
        $this->assertFalse($type->fresh()->is_active);
        $this->patch(route('request-types.toggle', $type))->assertRedirect();
        $this->assertTrue($type->fresh()->is_active);
        $this->delete(route('request-types.destroy', $type))->assertRedirect();
        $this->assertSoftDeleted($type);
    }

    public function test_student_field_configuration_is_ignored_and_name_and_price_are_validated(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $this->post(route('request-types.store'), ['name' => '', 'fee' => -1])->assertSessionHasErrors(['name', 'fee']);
        $this->post(route('request-types.store'), ['name' => 'Form 137', 'fee' => 0])->assertSessionHasErrors('name');
        $this->post(route('request-types.store'), ['name' => 'Custom transfer', 'fee' => 5, 'fields' => 'invalid old configuration', 'description' => 'ignored', 'is_active' => 0])->assertSessionHasNoErrors();
        $type = RequestType::firstOrFail();
        $this->assertSame([], $type->fields);
        $this->assertNull($type->description);
        $this->assertTrue($type->is_active);
        $this->post(route('request-types.store'), ['name' => $type->name, 'fee' => 5])->assertSessionHasErrors('name');
    }

    public function test_embedded_create_card_is_separate_and_preserves_validation_and_success_redirect(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $this->assertFalse(Route::has('request-types.create'));
        $this->get('/request-types/create')->assertStatus(405);
        $response = $this->get(route('request-types.index'))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $card = $xpath->query('//*[@id="add-custom-request-form"]')->item(0);
        $this->assertNotNull($card);
        $this->assertSame(1, $xpath->query('preceding-sibling::div[.//table]', $card)->length);
        $this->assertSame(0, $xpath->query('.//table', $card)->length);
        $this->from(route('request-types.index'))->post(route('request-types.store'), [
            '_form' => 'create-request-type', 'name' => 'Retained name', 'fee' => -5,
        ])->assertRedirect(route('request-types.index'))->assertSessionHasErrors('fee');
        $this->get(route('request-types.index'))->assertOk()->assertSee('value="Retained name"', false)->assertSee('value="-5"', false);
        $this->post(route('request-types.store'), ['name' => 'New custom form', 'fee' => 25])
            ->assertRedirect(route('request-types.index'))->assertSessionHas('success', 'Request form created.');
        $this->get(route('request-types.index'))->assertOk()->assertSee('Request form created.')->assertSee('New custom form');
    }

    public function test_edit_modal_preserves_values_validation_and_update_redirect(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $type = $this->type()->refresh();
        $this->get('/request-types/'.$type->id.'/edit')->assertNotFound();
        $response = $this->get(route('request-types.index'))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $dialog = $xpath->query('//dialog[@id="edit-request-type-'.$type->id.'"]')->item(0);
        $this->assertNotNull($dialog);
        $this->assertSame($type->name, $xpath->query('.//input[@name="name"]', $dialog)->item(0)->getAttribute('value'));
        $this->assertSame($type->fee, $xpath->query('.//input[@name="fee"]', $dialog)->item(0)->getAttribute('value'));
        $this->assertSame(1, $xpath->query('//button[@data-edit-dialog="edit-request-type-'.$type->id.'"]')->length);
        $this->from(route('request-types.index'))->put(route('request-types.update', $type), [
            '_form' => 'edit-request-type-'.$type->id, 'name' => 'Retained edit', 'fee' => -1, 'version' => $type->version,
        ])->assertRedirect(route('request-types.index'))->assertSessionHasErrors('fee');
        $this->get(route('request-types.index'))->assertOk()->assertSee(' data-reopen ', false)->assertSee('value="Retained edit"', false);
        $this->put(route('request-types.update', $type), [
            '_form' => 'edit-request-type-'.$type->id, 'name' => 'Updated custom form', 'fee' => 42.50, 'version' => $type->version,
        ])->assertRedirect(route('request-types.index'))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->get(route('request-types.index'))->assertOk()->assertSee('Updated custom form')->assertSee('42.50')->assertDontSee(' data-reopen ', false);
        $this->assertSame('Updated custom form', $type->fresh()->name);
        $this->assertSame('42.50', $type->fresh()->fee);
    }

    public function test_only_registrar_can_manage_custom_types(): void
    {
        $type = $this->type();
        foreach (['student', 'admin', 'records_officer'] as $role) {
            $this->actingAs($role === 'student' ? $this->student() : User::factory()->create(['role' => $role]));
            $this->get(route('request-types.index'))->assertForbidden();
            $this->post(route('request-types.store'), ['name' => 'Unauthorized', 'fee' => 0])->assertForbidden();
            $this->put(route('request-types.update', $type), [])->assertForbidden();
            $this->patch(route('request-types.toggle', $type))->assertForbidden();
            $this->delete(route('request-types.destroy', $type))->assertForbidden();
        }
    }

    public function test_student_submits_without_legacy_fields_and_registrar_receives_and_manually_processes_request(): void
    {
        // Existing definitions must no longer require their former custom fields.
        $type = $this->type();
        $type->update(['fields' => [['key' => 'reason', 'label' => 'Legacy required field', 'type' => 'text', 'required' => true, 'options' => []]]]);
        $this->actingAs($this->student())->get(route('student.request'))->assertOk()
            ->assertSee($type->name)->assertDontSee('Legacy required field')->assertDontSee('name="dynamic[', false);
        $this->post(route('student.request.store'), [...$this->payload($type), 'document_price' => 1, 'dynamic' => ['reason' => 'ignored']])->assertSessionHasNoErrors();
        $document = RequestDocument::firstOrFail();
        $this->assertSame('35.50', $document->document_price);
        $this->assertNull($document->dynamic_values);
        $this->assertArrayNotHasKey('fields', $document->form_snapshot);
        $this->assertSame('pending', $document->status);
        $this->assertSame('pending_clearance', $document->clearance_status);
        $this->assertFalse($document->payment_confirmed);
        $this->get(route('requests.receipt', $document))->assertOk()->assertDontSee('Submitted form details');
        $registrar = User::factory()->create(['role' => 'registrar']);
        $this->actingAs($registrar)->get(route('requests.index'))->assertOk()->assertSee($document->ticket_number)->assertSee('prepared manually');
        foreach (['processing', 'processed', 'ready_to_release', 'completed'] as $status) {
            $this->post(route('requests.update-status', $document), ['status' => $status])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame($status, $document->fresh()->status);
        }
        $this->get(route('requests.history'))->assertOk()->assertSee($document->ticket_number);
        $this->assertDatabaseCount('document_authenticities', 0);
        $this->assertDatabaseCount('document_artifacts', 0);
        $this->assertFalse(Route::has('requests.prepare-custom'));
    }

    public function test_custom_names_resembling_built_ins_never_offer_generator_tools(): void
    {
        $type = $this->type();
        $type->update(['name' => 'Certificate of Custom Recognition']);
        $this->actingAs($this->student())->post(route('student.request.store'), $this->payload($type))->assertSessionHasNoErrors();
        $document = RequestDocument::firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        foreach (['pending', 'processing', 'processed'] as $status) {
            $document->update(['status' => $status]);
            $this->get(route('requests.index'))->assertOk()->assertSee($document->ticket_number)
                ->assertDontSee(route('certifications.index', ['request_id' => $document->id]), false)
                ->assertDontSee('No generated document available')->assertDontSee('Sign prepared PDF');
        }
        $this->actingAs(User::factory()->create(['role' => 'records_officer']))->get(route('requests.index'))
            ->assertOk()->assertDontSee('Generate and download the protected document');
    }

    public function test_disabled_and_archived_types_cannot_be_requested_and_duplicates_still_block_after_rename(): void
    {
        $type = $this->type();
        $this->actingAs($this->student());
        $type->update(['is_active' => false]);
        $this->get(route('student.request'))->assertOk()->assertDontSee($type->name);
        $this->post(route('student.request.store'), $this->payload($type))->assertSessionHasErrors('document_type');
        $type->update(['is_active' => true]);
        $this->post(route('student.request.store'), $this->payload($type))->assertSessionHasNoErrors();
        $document = RequestDocument::firstOrFail();
        $type->update(['name' => 'Renamed transfer', 'fee' => 99]);
        $this->post(route('student.request.store'), $this->payload($type))->assertSessionHas('error');
        $type->delete();
        $this->post(route('student.request.store'), $this->payload($type))->assertSessionHasErrors('document_type');
        $this->assertSame('Custom transfer', $document->fresh()->document_type);
        $this->assertSame('35.50', $document->fresh()->document_price);
        $this->assertDatabaseCount('request_documents', 1);
    }

    public function test_registrar_visibility_and_generation_rules_for_builtin_documents_are_unchanged(): void
    {
        $student = $this->student();
        $document = RequestDocument::create(['student_number' => $student->student_number, 'ticket_number' => 'BUILT-IN-TEST', 'document_type' => 'Form 137', 'status' => 'pending']);
        $this->actingAs(User::factory()->create(['role' => 'registrar']))->get(route('requests.index'))->assertOk()->assertDontSee('BUILT-IN-TEST');
        $document->update(['status' => 'processing']);
        $this->actingAs(User::factory()->create(['role' => 'records_officer']))
            ->post(route('requests.update-status', $document), ['status' => 'processed'])->assertSessionHasErrors('status');
        $document->update(['status' => 'processed']);
        $this->actingAs(User::factory()->create(['role' => 'registrar']))->get(route('requests.index'))->assertOk()->assertSee('BUILT-IN-TEST');
    }

    public function test_historical_answers_and_private_attachments_remain_accessible_only_to_authorized_viewers(): void
    {
        $type = $this->type();
        $owner = $this->student();
        Storage::disk('local')->put('request-attachments/old.pdf', 'historical attachment');
        $document = RequestDocument::create(['student_number' => $owner->student_number, 'document_type' => $type->name, 'request_type_id' => $type->id, 'status' => 'pending',
            'form_snapshot' => ['fields' => [['key' => 'attachment', 'label' => 'Historical attachment', 'type' => 'file']]],
            'dynamic_values' => ['attachment' => ['path' => 'request-attachments/old.pdf', 'name' => 'old.pdf']],
        ]);
        $type->delete();
        $this->actingAs($owner)->get(route('requests.receipt', $document))->assertOk()->assertSee('Historical attachment');
        $this->get(route('requests.dynamic-attachment', [$document, 'attachment']))->assertOk();
        $this->actingAs($this->student('2026-9002'))->get(route('requests.dynamic-attachment', [$document, 'attachment']))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'registrar']))->get(route('requests.dynamic-attachment', [$document, 'attachment']))->assertOk();
    }

    private function type(): RequestType
    {
        return RequestType::create(['name' => 'Custom transfer', 'fee' => 35.50, 'is_active' => true, 'fields' => []]);
    }

    private function student(string $number = '2026-9001'): User
    {
        Student::create(['student_number' => $number, 'name' => 'Student', 'official_email' => $number.'@example.test']);

        return User::factory()->create(['role' => 'student', 'student_number' => $number, 'email' => $number.'@example.test']);
    }

    private function payload(RequestType $type): array
    {
        return ['document_type' => $type->name, 'payment_method' => 'cash', 'delivery_method' => 'pickup', 'transcript_receipt' => UploadedFile::fake()->image('receipt.png')];
    }
}
