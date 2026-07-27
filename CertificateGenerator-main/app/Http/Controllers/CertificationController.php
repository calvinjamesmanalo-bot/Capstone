<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CertificationController extends Controller
{
    public function index()
    {
        return view('certifications.index', [
            'certificateTypes' => $this->certificateTypes(),
            'form' => old() ?: $this->defaultForm(),
        ]);
    }

    public function preview(Request $request)
    {
        $form = $this->validatedForm($request);

        return view('certifications.preview', $this->viewData($form));
    }

    public function pdf(Request $request)
    {
        $form = $this->validatedForm($request);
        $data = $this->viewData($form);

        $pdf = Pdf::loadView('certifications.pdf', $data)->setPaper('a4', 'portrait');
        $filename = $this->filename($form, $data['certificate']['label']);

        if ($request->input('output') === 'stream') {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    private function validatedForm(Request $request): array
    {
        $data = $request->validate([
            'certificate_type' => ['required', Rule::in(array_keys($this->certificateTypes()))],
            'student_name' => ['required', 'string', 'max:120'],
            'lrn' => ['nullable', 'digits:12'],
            'grade_level' => ['required', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:50'],
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'issue_date' => ['required', 'date'],
            'purpose' => ['nullable', 'string', 'max:180'],
            'recognition' => ['nullable', 'required_if:certificate_type,recognition', 'string', 'max:180'],
        ], [
            'lrn.digits' => 'The LRN must be exactly 12 digits.',
            'school_year.regex' => 'Use the format YYYY-YYYY for the school year.',
            'recognition.required_if' => 'Enter the recognition title for a Certificate of Recognition.',
        ]);

        $data['lrn'] = trim($data['lrn'] ?? '');
        $data['section'] = trim($data['section'] ?? '');
        $data['purpose'] = trim($data['purpose'] ?? '');
        $data['recognition'] = trim($data['recognition'] ?? '');

        return $data;
    }

    private function viewData(array $form): array
    {
        $certificate = $this->certificateTypes()[$form['certificate_type']];
        $form['formatted_issue_date'] = $this->formattedIssueDate($form['issue_date']);

        return [
            'certificate' => $certificate,
            'certificateTypes' => $this->certificateTypes(),
            'assets' => [
                'template' => $this->firstImageDataUri([
                    'certificates/certificate-template.svg',
                ]),
                'seal' => $this->firstImageDataUri([
                    'certificates/fla-seal.png',
                    'certificates/fla-seal.jpg',
                    'certificates/fla-seal.svg',
                ]),
            ],
            'form' => $form,
            'student' => [
                'name' => $form['student_name'],
                'lrn' => $form['lrn'],
                'grade_level' => $form['grade_level'],
                'section' => $form['section'],
                'school_year' => $form['school_year'],
                'grade_section' => $this->gradeSection($form['grade_level'], $form['section']),
            ],
        ];
    }

    private function defaultForm(): array
    {
        return [
            'certificate_type' => '',
            'student_name' => '',
            'lrn' => '',
            'grade_level' => '',
            'section' => '',
            'school_year' => '',
            'issue_date' => '',
            'purpose' => '',
            'recognition' => '',
        ];
    }

    private function certificateTypes(): array
    {
        return [
            'enrollment' => [
                'label' => 'Certificate of Enrollment',
                'title' => 'CERTIFICATION',
                'signatory' => 'SHERYL F. FAX, MAEd.',
                'position' => 'Junior High School Principal',
            ],
            'completion' => [
                'label' => 'Certificate of Completion',
                'title' => 'CERTIFICATION',
                'signatory' => 'MARILYN M. ESTIPONA',
                'position' => 'Registrar',
            ],
            'good_moral' => [
                'label' => 'Certificate of Good Moral Character',
                'title' => 'GOOD CHARACTER CERTIFICATE',
                'signatory' => 'JOSEPHINE G. AMADOR, LPT',
                'position' => 'Guidance Associate',
            ],
            'recognition' => [
                'label' => 'Certificate of Recognition',
                'title' => 'CERTIFICATION',
                'signatory' => 'ROSAHLE S. PAGADORA, MS',
                'position' => 'SHS Principal',
            ],
        ];
    }

    private function gradeSection(string $gradeLevel, string $section): string
    {
        return trim($gradeLevel.($section !== '' ? ' - '.$section : ''));
    }

    private function formattedIssueDate(string $date): string
    {
        $parsed = Carbon::parse($date);

        return $parsed->day.$this->ordinal($parsed->day).' day of '.$parsed->format('F, Y');
    }

    private function firstImageDataUri(array $relativePaths): ?string
    {
        foreach ($relativePaths as $relativePath) {
            $image = $this->imageDataUri($relativePath);

            if ($image !== null) {
                return $image;
            }
        }

        return null;
    }

    private function imageDataUri(string $relativePath): ?string
    {
        $path = public_path($relativePath);

        if (! is_file($path)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            default => mime_content_type($path) ?: 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }

    private function ordinal(int $day): string
    {
        if (in_array($day % 100, [11, 12, 13], true)) {
            return 'th';
        }

        return match ($day % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }

    private function filename(array $form, string $label): string
    {
        return Str::slug($label.' '.$form['student_name'].' '.$form['school_year']).'.pdf';
    }
}
