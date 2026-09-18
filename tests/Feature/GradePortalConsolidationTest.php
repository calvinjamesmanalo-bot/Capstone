<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradePortalConsolidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_uses_one_grade_portal_with_student_and_class_sheet_tabs(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));

        $this->get(route('grade-portal.index'))
            ->assertOk()
            ->assertSee('Class Grade Sheets')
            ->assertSee(route('school-forms.records'), false)
            ->assertSee('Grade Portal');

        $this->get(route('school-forms.records'))
            ->assertOk()
            ->assertSee('Upload grade sheets')
            ->assertSee('Student Form 138 Records')
            ->assertSee(route('grade-portal.index'), false);

        $this->get('/school-forms/records')
            ->assertRedirect(route('school-forms.records'));
    }

    public function test_records_officer_cannot_open_grade_sheet_module_or_upload(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'records_officer']));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Grade Sheet Records')
            ->assertDontSee('Grade Sheet Upload');

        $this->get(route('school-forms.records'))->assertForbidden();
        $this->get('/school-forms/records')->assertForbidden();
        $this->post(route('school-forms.grade-sheets.store'))->assertForbidden();
        $this->get(route('school-forms.grade-sheets.template', ['type' => 'summary']))->assertForbidden();
        $this->get(route('school-forms.home'))->assertOk()->assertDontSee('Class Grade Sheets');
    }
}
