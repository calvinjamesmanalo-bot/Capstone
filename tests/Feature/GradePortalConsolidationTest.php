<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradePortalConsolidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_uses_one_grade_portal_for_student_search_and_class_sheets(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));

        $this->get(route('grade-portal.index'))
            ->assertOk()
            ->assertSee('Find student grades')
            ->assertSee('Browse class sheets')
            ->assertSee('Upload grade sheets')
            ->assertSee('data-portal-tabs data-default-panel="student"', false)
            ->assertSee('data-portal-panel="class"', false)
            ->assertSee('data-portal-panel="upload"', false)
            ->assertDontSee('Student Form 138 Records');

        $this->get(route('school-forms.records'))
            ->assertOk()
            ->assertSee('Upload grade sheets')
            ->assertSee('Find student grades')
            ->assertDontSee('Student Form 138 Records');

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
