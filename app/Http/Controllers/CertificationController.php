<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\RequestDocument;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CertificationController extends Controller
{
    public function index(Request $request)
    {
        $form = array_merge($this->defaultForm(), [
            'request_id' => $request->input('request_id', ''),
            'student_number' => $request->input('student_number', ''),
            'issue_date' => now()->toDateString(),
        ]);

        if ($request->filled('request_id')) {
            $documentRequest = RequestDocument::with('student')->find($request->input('request_id'));
            $type = collect($this->certificateTypes())->search(
                fn (array $certificate) => $certificate['label'] === $documentRequest?->document_type
            );

            if ($documentRequest && $type !== false) {
                $form['certificate_type'] = $type;
                $form['student_number'] = $documentRequest->student_number;
                $form['student_name'] = $documentRequest->student?->name ?? '';
            }
        }

        return view('certifications.index', [
            'students' => Student::orderBy('name')->get(),
            'certificateTypes' => $this->certificateTypes(),
            'form' => old() ?: $form,
        ]);
    }

    public function preview(Request $request)
    {
        return view('certifications.preview', $this->viewData($this->validatedForm($request)));
    }

    public function pdf(Request $request)
    {
        $form = $this->validatedForm($request);
        $data = $this->viewData($form);
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('certifications.pdf', $data)->render());
        $pdf->setPaper('a4', 'portrait');
        $pdf->render();

        $filename = Str::slug($data['certificate']['label'].' '.$form['student_name'].' '.$form['school_year']).'.pdf';

        return $pdf->stream($filename, ['Attachment' => $request->input('output') !== 'stream']);
    }

    private function validatedForm(Request $request): array
    {
        $data = $request->validate([
            'certificate_type' => ['required', Rule::in(array_keys($this->certificateTypes()))],
            'request_id' => ['nullable', 'integer', 'exists:request_documents,id'],
            'student_number' => ['nullable', 'string', 'max:50'],
            'student_name' => ['required', 'string', 'max:120'],
            'grade_level' => ['required', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:50'],
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'issue_date' => ['required', 'date'],
            'purpose' => ['nullable', 'string', 'max:180'],
            'recognition' => ['nullable', 'required_if:certificate_type,recognition', 'string', 'max:180'],
        ], [
            'school_year.regex' => 'Use the format YYYY-YYYY for the school year.',
            'recognition.required_if' => 'Enter the recognition title.',
        ]);

        $data['request_id'] = $data['request_id'] ?? '';

        foreach (['student_number', 'section', 'purpose', 'recognition'] as $field) {
            $data[$field] = trim($data[$field] ?? '');
        }

        return $data;
    }

    private function viewData(array $form): array
    {
        $date = Carbon::parse($form['issue_date']);
        $form['formatted_issue_date'] = $date->day.$this->ordinal($date->day).' day of '.$date->format('F, Y');
        $form['grade_section'] = trim($form['grade_level'].($form['section'] ? ' - '.$form['section'] : ''));

        return [
            'certificate' => $this->certificateTypes()[$form['certificate_type']],
            'form' => $form,
            'student' => [
                'name' => $form['student_name'],
                'grade_level' => $form['grade_level'],
                'section' => $form['section'],
                'school_year' => $form['school_year'],
                'grade_section' => $form['grade_section'],
            ],
            'assets' => [
                'template' => $this->imageDataUri(public_path('certificates/certificate-template.svg')),
                'seal' => $this->imageDataUri(public_path('certificates/fla-seal.jpg')),
            ],
        ];
    }

    private function defaultForm(): array
    {
        return ['request_id' => '', 'certificate_type' => '', 'student_number' => '', 'student_name' => '', 'grade_level' => '', 'section' => '', 'school_year' => '', 'issue_date' => '', 'purpose' => '', 'recognition' => ''];
    }

    private function certificateTypes(): array
    {
        return [
            'enrollment' => ['label' => 'Certificate of Enrollment', 'title' => 'CERTIFICATION', 'signatory' => 'SHERYL F. FAX, MAEd.', 'position' => 'Junior High School Principal'],
            'completion' => ['label' => 'Certificate of Completion', 'title' => 'CERTIFICATION', 'signatory' => 'MARILYN M. ESTIPONA', 'position' => 'Registrar'],
            'good_moral' => ['label' => 'Certificate of Good Moral Character', 'title' => 'GOOD CHARACTER CERTIFICATE', 'signatory' => 'JOSEPHINE G. AMADOR, LPT', 'position' => 'Guidance Associate'],
            'recognition' => ['label' => 'Certificate of Recognition', 'title' => 'CERTIFICATION', 'signatory' => 'ROSAHLE S. PAGADORA, MS', 'position' => 'SHS Principal'],
        ];
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

    private function imageDataUri(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }
}
