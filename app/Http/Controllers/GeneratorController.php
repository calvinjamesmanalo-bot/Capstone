<?php

namespace App\Http\Controllers;

use App\Models\RequestDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GeneratorController extends Controller
{
    public function maker(Request $request, ?RequestDocument $documentRequest = null): RedirectResponse
    {
        $this->authorizeStaff(['admin', 'records_officer']);

        $form = strtolower((string) $request->query('form', 'f138'));
        abort_unless(in_array($form, ['f137', 'f138'], true), 404);

        if ($documentRequest) {
            abort_unless($this->formFor($documentRequest) === $form, 404);
        }

        $parameters = [];
        if ($documentRequest) {
            $parameters['student'] = $documentRequest->student_number;
            if ($form === 'f138') {
                $parameters['school_year'] = $documentRequest->school_year;
            }
            $parameters['request_id'] = $documentRequest->id;
        }

        if (! $documentRequest) {
            return redirect()->route('school-forms.home', ['form' => $form]);
        }

        return redirect()->route("school-forms.{$form}.preview", $parameters);
    }

    public function gradeSheets(): RedirectResponse
    {
        $this->authorizeStaff(['admin', 'registrar', 'records_officer']);

        return redirect()->route('school-forms.records');
    }

    private function authorizeStaff(array $roles): void
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, $roles, true), 403);
    }

    private function formFor(RequestDocument $request): ?string
    {
        return match (strtolower(trim($request->document_type))) {
            'form 137', 'f137' => 'f137',
            'form 138', 'f138' => 'f138',
            default => null,
        };
    }
}
