<?php

namespace Tests\Feature;

use App\Http\Controllers\SchoolFormF138Controller;
use App\Http\Controllers\SchoolFormRecordController;
use App\Support\AcademicPeriod;
use App\Support\CurriculumSubjects;
use Tests\TestCase;

class ShsF138DocumentTest extends TestCase
{
    public function test_senior_high_school_levels_are_available_for_grade_sheet_imports(): void
    {
        $this->assertContains('Grade 11', config('academics.grade_levels'));
        $this->assertContains('Grade 12', config('academics.grade_levels'));
        $this->assertSame(
            ['Grade 11', 'Grade 12'],
            config('academics.grade_level_groups.Senior High School (Grade 11 to Grade 12)'),
        );
    }

    public function test_three_term_grade_11_f138_is_labeled_as_senior_high_school(): void
    {
        $html = $this->renderF138('school-forms.pdf.f138-three-term-template', '2026-2027', 'Grade 11');

        $this->assertStringContainsString('Senior High School', $html);
        $this->assertStringContainsString('PROMOTED TO GRADE 12', $html);
        $this->assertStringNotContainsString('COMPLETED JUNIOR HIGH SCHOOL', $html);
    }

    public function test_grade_12_f138_uses_the_senior_high_school_completion_outcome(): void
    {
        foreach ([
            ['school-forms.pdf.f138-template', '2025-2026'],
            ['school-forms.pdf.f138-three-term-template', '2026-2027'],
        ] as [$view, $schoolYear]) {
            $html = $this->renderF138($view, $schoolYear, 'Grade 12');

            $this->assertStringContainsString('Senior High School', $html);
            $this->assertStringContainsString('COMPLETED SENIOR HIGH SCHOOL', $html);
            $this->assertStringNotContainsString('PROMOTED TO GRADE 13', $html);
        }
    }

    public function test_transition_year_grade_12_keeps_four_grading_periods_and_uses_the_shs_semester_form(): void
    {
        $this->assertSame([1, 2, 3, 4], AcademicPeriod::numbers('2026-2027', 'Grade 12'));
        $this->assertSame([1, 2, 3], AcademicPeriod::numbers('2026-2027', 'Grade 11'));

        $method = new \ReflectionMethod(SchoolFormF138Controller::class, 'pdfView');
        $view = $method->invoke(app(SchoolFormF138Controller::class), '2026-2027', 'Grade 12');
        $html = $this->renderF138($view, '2026-2027', 'Grade 12');

        $this->assertSame('school-forms.pdf.f138-shs-semester-template', $view);
        $this->assertStringContainsString('1ST SEMESTER', $html);
        $this->assertStringContainsString('2ND SEMESTER', $html);
        $this->assertStringContainsString('GENERAL WEIGHTED AVERAGE', $html);
        $this->assertStringContainsString('GENERAL MATHEMATICS', $html);
    }

    public function test_grade_portal_uses_section_specific_shs_subjects(): void
    {
        $this->assertContains('Finite Mathematics 1', CurriculumSubjects::for('Grade 11', 'Aristotle'));
        $this->assertContains('Human Movement 1 (Basic Anatomy in Sports and Exercise)', CurriculumSubjects::for('Grade 11', 'Copernicus'));
        $this->assertContains('Bakery Operations', CurriculumSubjects::for('Grade 11', 'Hawking'));
        $this->assertContains('General Chemistry 2', CurriculumSubjects::for('Grade 12', 'Archimedes'));
        $this->assertContains('Business Finance', CurriculumSubjects::for('Grade 12', 'Jobs'));
        $this->assertContains('Creative Writing', CurriculumSubjects::for('Grade 12', 'Descartes'));
        $this->assertContains('Caregiving (NC II) 2', CurriculumSubjects::for('Grade 12', 'Fleming'));
    }

    public function test_grade_portal_exposes_the_official_sections_from_the_enrollment_report(): void
    {
        $this->assertSame(['A', 'B', 'C'], config('academics.sections_by_grade.Kinder'));
        $this->assertContains('Amity', config('academics.sections_by_grade.Grade 1'));
        $this->assertContains('Diligence', config('academics.sections_by_grade.Grade 2'));
        $this->assertContains('Hydrogen', config('academics.sections_by_grade.Grade 7'));
        $this->assertContains('Mercury', config('academics.sections_by_grade.Grade 8'));
        $this->assertContains('Plutonium', config('academics.sections_by_grade.Grade 9'));
        $this->assertContains('Xenon', config('academics.sections_by_grade.Grade 10'));
        $this->assertContains('Gates', config('academics.sections_by_grade.Grade 11'));
        $this->assertContains('Pasteur', config('academics.sections_by_grade.Grade 12'));
    }

    public function test_grade_portal_summary_template_uses_the_selected_sections_subjects(): void
    {
        $method = new \ReflectionMethod(SchoolFormRecordController::class, 'makeTemplate');
        $sheet = $method->invoke(app(SchoolFormRecordController::class), 'summary', [
            'school_year' => '2026-2027',
            'level' => 'Grade 11',
            'section' => 'Copernicus',
            'period' => 1,
        ])->getSheetByName('Summary Sheet');

        $headers = $sheet->rangeToArray('A6:K6', null, true, true, false)[0];
        $this->assertContains('HUMAN MOVEMENT 1 (BASIC ANATOMY IN SPORTS AND EXERCISE)', $headers, json_encode($headers));
        $this->assertNotContains('FINITE MATHEMATICS 1', $headers);
        $this->assertSame('GEN. AVE.', end($headers));
    }

    private function renderF138(string $view, string $schoolYear, string $level): string
    {
        return view($view, [
            'student' => (object) [
                'student_number' => 'SHS-0001',
                'lrn' => '123456789012',
                'name' => 'Senior High Learner',
            ],
            'enrollment' => (object) [
                'school_year' => $schoolYear,
                'level' => $level,
                'section' => 'STEM 1',
                'adviser_name' => 'Test Adviser',
            ],
            'gradeMatrix' => [
                'General Mathematics' => [1 => 90, 2 => 91, 3 => 92, 4 => 93],
            ],
            'attendance' => [],
            'documentMode' => 'draft',
            'requestId' => null,
        ])->render();
    }
}
