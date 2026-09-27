<?php

namespace Tests\Feature;

use App\Models\RequestDocument;
use App\Models\RequestType;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Support\RequestCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestPricingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_has_no_pricing_and_saving_it_preserves_existing_prices(): void
    {
        Setting::create(['key' => 'price_form_137', 'value' => '175.50', 'group' => 'pricing']);
        Setting::create(['key' => 'student_idle_timeout_minutes', 'value' => '25']);
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $page = $this->get(route('settings.index'))->assertOk()->assertDontSee('Document price list')->assertDontSee('Save Document Prices');
        foreach (RequestCatalog::BUILT_INS as [$key]) {
            $page->assertDontSee('name="'.$key.'"', false);
        }
        $this->post(route('settings.update'), ['school_name' => 'Updated school', 'price_form_137' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('settings', ['key' => 'school_name', 'value' => 'Updated school']);
        $this->assertDatabaseHas('settings', ['key' => 'price_form_137', 'value' => '175.50']);
        $this->assertDatabaseHas('settings', ['key' => 'student_idle_timeout_minutes', 'value' => '25']);
    }

    public function test_unified_list_displays_existing_builtin_and_custom_prices_without_creating_duplicates(): void
    {
        Setting::create(['key' => 'price_form_137', 'value' => '175.50', 'group' => 'pricing']);
        RequestType::create(['name' => 'Certificate of Ranking', 'fee' => 77.25, 'fields' => [], 'is_active' => true]);
        $page = $this->actingAs(User::factory()->create(['role' => 'registrar']))->get(route('request-types.index'))->assertOk();
        foreach (array_keys(RequestCatalog::BUILT_INS) as $name) {
            $page->assertSee($name);
        }
        $page->assertSee('175.50')->assertSee('77.25')->assertSee('Built-in')->assertSee('Custom')->assertSee('Enabled')->assertSee('Edit Price');
        $this->assertDatabaseCount('request_types', 1);
        $this->assertDatabaseCount('settings', 1);
    }

    public function test_each_builtin_price_uses_existing_setting_and_student_submission_uses_the_updated_fee(): void
    {
        Storage::fake('local');
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->student();
        foreach (RequestCatalog::BUILT_INS as $name => [$key]) {
            $this->actingAs($registrar)->patch(route('request-types.built-in.price', $key), ['fee' => 210.75, 'name' => 'Forged rename'])->assertSessionHasNoErrors();
            $this->patch(route('request-types.built-in.price', $key), ['fee' => 220.50])->assertSessionHasNoErrors();
            $this->assertSame(1, Setting::where('key', $key)->count());
            $this->assertSame(220.50, RequestCatalog::prices()[$name]);
            $this->actingAs($student)->get(route('student.request'))->assertOk()->assertSee('data-price="220.50"', false);
            $payload = ['document_type' => $name, 'document_price' => 1, 'delivery_method' => 'pickup', 'payment_method' => 'cash',
                'transcript_receipt' => UploadedFile::fake()->image('receipt.png'),
            ];
            if ($name === 'Form 137') {
                $payload['school_level'] = 'elementary';
            }
            if ($name === 'Form 138') {
                $payload['school_year'] = config('academics.school_years')[0];
            }
            $this->post(route('student.request.store'), $payload)->assertSessionHasNoErrors();
            $document = RequestDocument::where('document_type', $name)->firstOrFail();
            $this->assertSame('220.50', $document->document_price);
            $this->assertNull($document->request_type_id);
            $this->actingAs($registrar)->patch(route('request-types.built-in.price', $key), ['fee' => 0])->assertSessionHasNoErrors();
            $this->assertSame('220.50', $document->fresh()->document_price);
        }
        $this->assertDatabaseCount('request_types', 0);
    }

    public function test_builtin_disable_hides_option_and_blocks_stale_submissions_without_affecting_existing_requests(): void
    {
        Storage::fake('local');
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->student();
        $document = RequestDocument::create(['student_number' => $student->student_number, 'document_type' => 'Diploma', 'status' => 'processing', 'document_price' => 150]);
        $this->actingAs($registrar)->patch(route('request-types.built-in.availability', 'price_diploma'), ['enabled' => 0])->assertSessionHasNoErrors();
        $this->actingAs($student)->get(route('student.request'))->assertOk()->assertDontSee('name="document_type" value="Diploma"', false);
        $payload = ['document_type' => 'Diploma', 'delivery_method' => 'pickup', 'payment_method' => 'cash', 'transcript_receipt' => UploadedFile::fake()->image('receipt.png')];
        $this->post(route('student.request.store'), $payload)->assertSessionHasErrors('document_type');
        $this->assertSame('processing', $document->fresh()->status);
        $this->assertSame('150.00', $document->fresh()->document_price);
        $this->actingAs($registrar)->patch(route('request-types.built-in.availability', 'price_diploma'), ['enabled' => 1])->assertSessionHasNoErrors();
        $this->actingAs($student)->get(route('student.request'))->assertOk()->assertSee('name="document_type" value="Diploma"', false);
    }

    public function test_builtin_controls_are_allowlisted_validated_registrar_only_and_cannot_archive_or_rename(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $this->patch(route('request-types.built-in.price', 'APP_KEY'), ['fee' => 5])->assertNotFound();
        $this->patch(route('request-types.built-in.availability', 'APP_KEY'), ['enabled' => 0])->assertNotFound();
        foreach ([-1, 'invalid', '1.234', 1000000] as $fee) {
            $this->patch(route('request-types.built-in.price', 'price_diploma'), ['fee' => $fee])->assertSessionHasErrors('fee');
        }
        $this->patch(route('request-types.built-in.availability', 'price_diploma'), ['enabled' => 'invalid'])->assertSessionHasErrors('enabled');
        $this->delete(route('request-types.destroy', 'price_diploma'))->assertNotFound();
        $this->put(route('request-types.update', 'price_diploma'), ['name' => 'Changed', 'fee' => 1])->assertNotFound();
        foreach (array_keys(RequestCatalog::BUILT_INS) as $name) {
            $this->post(route('request-types.store'), ['name' => strtolower($name), 'fee' => 1])->assertSessionHasErrors('name');
        }
        foreach (['admin', 'records_officer', 'student'] as $role) {
            $this->actingAs($role === 'student' ? $this->student() : User::factory()->create(['role' => $role]));
            $this->patch(route('request-types.built-in.price', 'price_diploma'), ['fee' => 5])->assertForbidden();
            $this->patch(route('request-types.built-in.availability', 'price_diploma'), ['enabled' => 0])->assertForbidden();
        }
        $this->assertDatabaseCount('settings', 0);
        $this->assertDatabaseCount('request_types', 0);
    }

    public function test_custom_price_update_is_used_by_student_without_creating_a_settings_price(): void
    {
        Storage::fake('local');
        $type = RequestType::create(['name' => 'Ranking', 'fee' => 10, 'is_active' => true, 'fields' => []]);
        $this->actingAs(User::factory()->create(['role' => 'registrar']))->put(route('request-types.update', $type), ['name' => 'Ranking', 'fee' => 88.25, 'version' => 1])->assertSessionHasNoErrors();
        $this->actingAs($this->student())->get(route('student.request'))->assertOk()->assertSee('data-price="88.25"', false);
        $this->post(route('student.request.store'), ['document_type' => 'Ranking', 'payment_method' => 'cash', 'delivery_method' => 'pickup', 'transcript_receipt' => UploadedFile::fake()->image('receipt.png')])->assertSessionHasNoErrors();
        $this->assertSame('88.25', RequestDocument::firstOrFail()->document_price);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_builtin_rows_never_offer_archive_or_delete_and_archived_list_is_custom_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $page = $this->get(route('request-types.index'))->assertOk();
        $page->assertDontSee('value="DELETE"', false)->assertDontSee('>Archive</button>', false);
        foreach (RequestCatalog::BUILT_INS as $name => [$key]) {
            $page->assertSee($name)
                ->assertSee(route('request-types.built-in.price', $key), false)
                ->assertSee(route('request-types.built-in.availability', $key), false);
            $this->delete(route('request-types.destroy', $key))->assertNotFound();
            $this->delete(route('request-types.built-in.price', $key))->assertStatus(405);
        }
        $this->get(route('request-types.index', ['view' => 'archived']))->assertOk()->assertSee('No archived custom forms.');
        $this->get(route('request-types.index', ['view' => 'invalid']))->assertSessionHasErrors('view');
        $this->assertDatabaseCount('request_types', 0);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_archiving_disables_custom_form_but_keeps_original_request_history(): void
    {
        Storage::fake('local');
        $type = RequestType::create(['name' => 'Original ranking', 'fee' => 88.25, 'is_active' => true, 'fields' => []]);
        $student = $this->student();
        $this->actingAs($student)->post(route('student.request.store'), [
            'document_type' => $type->name, 'payment_method' => 'cash', 'delivery_method' => 'pickup',
            'transcript_receipt' => UploadedFile::fake()->image('receipt.png'),
        ])->assertSessionHasNoErrors();
        $document = RequestDocument::firstOrFail();
        $document->update(['status' => 'completed']);
        $historyCount = $document->statusHistories()->count();
        $type->update(['name' => 'Renamed ranking', 'fee' => 99]);
        $this->actingAs(User::factory()->create(['role' => 'registrar']))->delete(route('request-types.destroy', $type))->assertRedirect();
        $archived = RequestType::withTrashed()->findOrFail($type->id);
        $this->assertTrue($archived->trashed());
        $this->assertFalse($archived->is_active);
        $this->get(route('request-types.index'))->assertOk()->assertDontSee('Renamed ranking');
        $this->get(route('request-types.index', ['view' => 'archived']))->assertOk()->assertSee('Renamed ranking')
            ->assertDontSee(route('request-types.toggle', $type), false)->assertDontSee('Edit Price');
        $this->patch(route('request-types.toggle', $type))->assertNotFound();
        $this->get(route('requests.history'))->assertOk()->assertSee('Original ranking');
        $this->actingAs($student)->get(route('student.request'))->assertOk()->assertDontSee('name="document_type" value="Renamed ranking"', false);
        $this->post(route('student.request.store'), [
            'document_type' => 'Renamed ranking', 'payment_method' => 'cash', 'delivery_method' => 'pickup',
            'transcript_receipt' => UploadedFile::fake()->image('receipt.png'),
        ])->assertSessionHasErrors('document_type');
        $this->assertDatabaseCount('request_documents', 1);
        $this->assertSame($historyCount, $document->statusHistories()->count());
        $this->assertSame('Original ranking', $document->fresh()->document_type);
        $this->assertSame('88.25', $document->fresh()->document_price);
        $this->assertSame('Original ranking', $document->form_snapshot['name']);
    }

    private function student(): User
    {
        Student::create(['student_number' => '2026-PRICE', 'name' => 'Pricing Student', 'official_email' => 'pricing@example.test']);

        return User::factory()->create(['role' => 'student', 'student_number' => '2026-PRICE', 'email' => 'pricing@example.test']);
    }
}
