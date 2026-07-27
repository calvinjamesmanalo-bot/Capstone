<?php

namespace Tests\Feature;

use Tests\TestCase;

class CertificationModuleTest extends TestCase
{
    public function test_certification_module_opens_with_blank_student_fields(): void
    {
        $this->get('/certifications')
            ->assertOk()
            ->assertSee('Certification Maker')
            ->assertSee('Student Name')
            ->assertDontSee('Sample Student Information')
            ->assertDontSee('value="Juan Dela Cruz"', false)
            ->assertDontSee('value="123456789012"', false);
    }

    public function test_good_moral_preview_populates_student_information(): void
    {
        $this->post(route('certifications.preview'), $this->validPayload([
            'certificate_type' => 'good_moral',
        ]))
            ->assertOk()
            ->assertSee('GOOD CHARACTER CERTIFICATE')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('Grade 10')
            ->assertSee('Rizal')
            ->assertSee('2026-2027');
    }

    public function test_certificate_pdf_download_is_generated(): void
    {
        $response = $this->post(route('certifications.pdf'), $this->validPayload([
            'certificate_type' => 'enrollment',
            'output' => 'download',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertPdfPageCount(1, $response->getContent());
    }

    public function test_good_moral_pdf_download_is_generated(): void
    {
        $response = $this->post(route('certifications.pdf'), $this->validPayload([
            'certificate_type' => 'good_moral',
            'output' => 'download',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertPdfPageCount(1, $response->getContent());
    }

    public function test_recognition_pdf_download_is_generated_on_one_page(): void
    {
        $response = $this->post(route('certifications.pdf'), $this->validPayload([
            'certificate_type' => 'recognition',
            'output' => 'download',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertPdfPageCount(1, $response->getContent());
    }

    private function assertPdfPageCount(int $expected, string $pdf): void
    {
        preg_match_all('/\/Type\s*\/Page\b/', $pdf, $matches);

        $this->assertCount($expected, $matches[0]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'certificate_type' => 'enrollment',
            'student_name' => 'Juan Dela Cruz',
            'lrn' => '123456789012',
            'grade_level' => 'Grade 10',
            'section' => 'Rizal',
            'school_year' => '2026-2027',
            'issue_date' => '2026-07-03',
            'purpose' => 'whatever legal purpose it may serve',
            'recognition' => 'Outstanding Academic Performance',
        ], $overrides);
    }
}
