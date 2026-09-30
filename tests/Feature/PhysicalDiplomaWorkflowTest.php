<?php

namespace Tests\Feature;

use App\Models\RequestDocument;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhysicalDiplomaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_diploma_is_processed_as_a_physical_copy_without_generation_routes(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = Student::create(['student_number' => '2026-0001', 'name' => 'Physical Diploma Student']);
        $document = RequestDocument::create([
            'student_number' => $student->student_number,
            'document_type' => 'Diploma',
            'status' => 'processing',
            'delivery_method' => 'pickup',
        ]);

        $this->actingAs($registrar)
            ->get(route('diploma.index', ['request_id' => $document->id]))
            ->assertOk()
            ->assertSee('Physical Diploma')
            ->assertSee('No PDF, preview, or digital diploma will be generated.')
            ->assertDontSee('Preview PDF')
            ->assertDontSee('Download PDF');

        $this->post('/diploma/generate')->assertNotFound();
        $this->get("/diploma/preview/{$document->id}")->assertNotFound();

        $this->post(route('diploma.submit'), [
            'request_id' => $document->id,
            'checklist' => [0, 1, 2, 3],
        ])->assertRedirect(route('requests.index'));

        $document->refresh();
        $this->assertSame('processed', $document->status);
        $this->assertStringContainsString('PHYSICAL COPY ONLY', $document->remarks);
        $this->assertFalse($document->hasPreparedDocument());
    }

    public function test_physical_diploma_requires_the_complete_clearance_checklist(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        Student::create(['student_number' => '2026-0002', 'name' => 'Incomplete Clearance Student']);
        $document = RequestDocument::create([
            'student_number' => '2026-0002',
            'document_type' => 'Diploma',
            'status' => 'processing',
        ]);

        $this->actingAs($registrar)->post(route('diploma.submit'), [
            'request_id' => $document->id,
            'checklist' => [0, 1, 2],
        ])->assertSessionHasErrors('checklist');

        $this->assertSame('processing', $document->fresh()->status);
    }
}
