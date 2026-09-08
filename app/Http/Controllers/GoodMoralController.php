<?php

namespace App\Http\Controllers;

use App\Models\RequestDocument;
use App\Models\Student;
use App\Support\DocumentQrCode;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class GoodMoralController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::orderBy('name')->get();
        $selectedStudent = null;
        $selectedPurpose = null;

        if ($request->has('request_id')) {
            $docRequest = RequestDocument::find($request->request_id);
            if ($docRequest && $docRequest->student) {
                $selectedStudent = $docRequest->student->name;

                // Extract purpose from remarks if it starts with "Purpose: "
                if (str_starts_with($docRequest->remarks, 'Purpose: ')) {
                    $selectedPurpose = substr($docRequest->remarks, 9);
                }
            }
        }

        return view('good-moral.index', compact('students', 'selectedStudent', 'selectedPurpose'));
    }

    public function submitToRegistrar(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:request_documents,id',
            'purpose' => 'nullable|string',
            'checklist' => 'required|array|size:4',
        ]);

        $docRequest = RequestDocument::findOrFail($request->request_id);

        $docRequest->update([
            'status' => 'processed',
            'remarks' => $request->purpose ? "Purpose: {$request->purpose}" : $docRequest->remarks,
        ]);

        record_log('Submitted Good Moral to Registrar', 'Requests', "Request #{$docRequest->id} submitted for approval");

        return redirect()->route('requests.index')->with('success', 'Good Moral has been submitted to Registrar for approval.');
    }

    public function previewFromRequest($id)
    {
        $docRequest = RequestDocument::with('student')->findOrFail($id);

        if (! $docRequest->student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }

        $name = $docRequest->student->name;
        $date = $docRequest->created_at->format('jS \d\a\y \o\f F, Y');

        // Extract purpose from remarks
        $purpose = 'any legal purpose it may serve';
        if (str_starts_with($docRequest->remarks, 'Purpose: ')) {
            $purpose = substr($docRequest->remarks, 9);
        }

        $qrContext = [
            'qrRequestId' => $docRequest->id,
            'qrHolderIdentifier' => $docRequest->student_number,
            'qrIssuedAt' => $docRequest->updated_at,
        ];
        $documentQr = app(DocumentQrCode::class)->make('Certificate of Good Moral Character', $name, [
            'request_id' => $docRequest->id,
            'holder_identifier' => $docRequest->student_number,
            'purpose' => $purpose,
            'issued_at' => $docRequest->updated_at,
        ]);
        $html = view('good-moral.pdf-template', compact('name', 'date', 'purpose', 'qrContext', 'documentQr'))->render();

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "Good_Moral_{$name}.pdf";
        $bytes = $dompdf->output();
        app(DocumentQrCode::class)->registerArtifact($documentQr['document'], $bytes, $filename, 'application/pdf');

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'student_name' => 'required|string',
            'request_id' => 'nullable|integer|exists:request_documents,id',
            'checklist' => 'required|array|size:4', // Siguraduhin na lahat ng checklist ay chineck
        ], [
            'checklist.required' => 'Dapat i-check lahat ng requirements bago mag-generate.',
            'checklist.size' => 'Dapat i-check lahat ng requirements bago mag-generate.',
        ]);

        $name = $request->student_name;
        $date = now()->format('jS \d\a\y \o\f F, Y');
        $purpose = $request->purpose ?? 'any legal purpose it may serve';

        $student = Student::where('name', $request->student_name)->first();
        $qrContext = [
            'qrRequestId' => $request->integer('request_id') ?: null,
            'qrHolderIdentifier' => $student?->student_number,
            'qrIssuedAt' => now(),
        ];

        $documentQr = app(DocumentQrCode::class)->make('Certificate of Good Moral Character', $name, [
            'request_id' => $request->integer('request_id') ?: null,
            'holder_identifier' => $student?->student_number,
            'purpose' => $purpose,
            'issued_at' => now(),
        ]);

        $html = view('good-moral.pdf-template', compact('name', 'date', 'purpose', 'qrContext', 'documentQr'))->render();

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "Good_Moral_{$name}.pdf";
        $bytes = $dompdf->output();
        app(DocumentQrCode::class)->registerArtifact($documentQr['document'], $bytes, $filename, 'application/pdf');

        $disposition = $request->has('preview') ? 'inline' : 'attachment';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
