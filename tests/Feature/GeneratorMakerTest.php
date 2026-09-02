<?php

namespace Tests\Feature;

use App\Models\RequestDocument;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneratorMakerTest extends TestCase
{
    use RefreshDatabase;

    public function test_f137_request_button_opens_the_f137_preview_directly(): void
    {
        $requestDocument = $this->documentRequest('Form 137');

        $this->actingAs(User::factory()->create(['role' => 'records_officer']))
            ->get(route('generator.maker', ['documentRequest' => $requestDocument, 'form' => 'f137']))
            ->assertRedirect(route('school-forms.f137.preview', [
                'student' => '2020-0001',
                'request_id' => $requestDocument->id,
            ]));
    }

    public function test_f138_request_button_opens_the_f138_preview_directly(): void
    {
        $requestDocument = $this->documentRequest('Form 138', '2021-2022');

        $this->actingAs(User::factory()->create(['role' => 'records_officer']))
            ->get(route('generator.maker', ['documentRequest' => $requestDocument, 'form' => 'f138']))
            ->assertRedirect(route('school-forms.f138.preview', [
                'student' => '2020-0001',
                'school_year' => '2021-2022',
                'request_id' => $requestDocument->id,
            ]));
    }

    public function test_records_officer_sidebar_hides_academic_records_without_blocking_routes(): void
    {
        $recordsOfficer = User::factory()->create(['role' => 'records_officer']);

        $this->actingAs($recordsOfficer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Academic Records')
            ->assertDontSee('Grade Sheet Upload')
            ->assertDontSee('Form 137 Maker')
            ->assertDontSee('Form 138 Maker')
            ->assertDontSee('Certification Maker')
            ->assertDontSee('Good Moral Maker');

        $requestDocument = $this->documentRequest('Form 138', '2021-2022');
        $this->actingAs($recordsOfficer)
            ->get(route('generator.maker', ['documentRequest' => $requestDocument, 'form' => 'f138']))
            ->assertRedirect(route('school-forms.f138.preview', [
                'student' => '2020-0001',
                'school_year' => '2021-2022',
                'request_id' => $requestDocument->id,
            ]));
    }

    private function documentRequest(string $documentType, ?string $schoolYear = null): RequestDocument
    {
        Student::create([
            'student_number' => '2020-0001',
            'name' => 'Test Student',
        ]);

        return RequestDocument::create([
            'student_number' => '2020-0001',
            'document_type' => $documentType,
            'school_year' => $schoolYear,
        ]);
    }
}
