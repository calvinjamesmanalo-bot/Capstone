<?php

namespace App\Http\Controllers;

use App\Models\RequestDocument;
use App\Models\Student;
use Illuminate\Http\Request;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Support\DocumentQrCode;

class DiplomaController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::orderBy('name')->get();
        $selectedStudent = null;
        $docRequest = null;

        if ($request->has('request_id')) {
            $docRequest = RequestDocument::find($request->request_id);
            if ($docRequest && $docRequest->student) {
                $selectedStudent = $docRequest->student->name;
            }
        }

        return view('diploma.index', compact('students', 'selectedStudent', 'docRequest'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'student_name' => 'required|string',
            'request_id' => 'nullable|integer|exists:request_documents,id',
            'checklist' => 'required|array|size:4',
            'course' => 'required|string',
            'graduation_date' => 'required|date',
        ]);

        $name = strtoupper($request->student_name);
        $course = strtoupper($request->course);
        $date = date('F d, Y', strtotime($request->graduation_date));

        $student = Student::where('name', $request->student_name)->first();
        $qrContext = [
            'qrRequestId' => $request->integer('request_id') ?: null,
            'qrHolderIdentifier' => $student?->student_number,
            'qrIssuedAt' => now(),
        ];

        $documentQr = app(DocumentQrCode::class)->make('Diploma', $name, [
            'request_id' => $request->integer('request_id') ?: null,
            'holder_identifier' => $student?->student_number,
            'issued_at' => now(),
            'fields' => ['course' => $course, 'graduation_date' => $date],
        ]);

        $html = view('diploma.pdf-template', compact('name', 'course', 'date', 'qrContext', 'documentQr'))->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = "Diploma_{$name}.pdf";
        $bytes = $dompdf->output();
        app(DocumentQrCode::class)->registerArtifact($documentQr['document'], $bytes, $filename, 'application/pdf');

        $disposition = $request->has('preview') ? 'inline' : 'attachment';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function submitToRegistrar(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:request_documents,id',
            'checklist' => 'required|array|size:4',
            'delivery_mode' => 'required|string|in:generator,pickup',
            'course' => 'required_if:delivery_mode,generator',
            'graduation_date' => 'required_if:delivery_mode,generator',
        ]);

        $docRequest = RequestDocument::findOrFail($request->request_id);
        
        $remarks = "Mode: " . strtoupper($request->delivery_mode);
        if ($request->delivery_mode === 'generator') {
            $remarks .= " | Course: {$request->course} | Grad Date: {$request->graduation_date}";
        }

        $docRequest->update([
            'status' => 'processed',
            'remarks' => $remarks
        ]);

        record_log('Submitted Diploma to Registrar', 'Requests', "Request #{$docRequest->id} submitted for approval ({$request->delivery_mode})");

        return redirect()->route('requests.index')->with('success', 'Diploma request has been submitted to Registrar for approval.');
    }

    public function previewFromRequest($id)
    {
        $docRequest = RequestDocument::with('student')->findOrFail($id);
        
        if (!$docRequest->student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }

        // If it's for pickup, just show a message or redirect back
        if (str_contains(strtoupper($docRequest->remarks), 'MODE: PICKUP')) {
            return redirect()->back()->with('info', 'This request is for physical copy pickup. No digital preview available.');
        }

        $name = strtoupper($docRequest->student->name);
        
        // Parse course and date from remarks
        $course = 'GENERAL SECONDARY EDUCATION';
        $date = $docRequest->created_at->format('F d, Y');

        if (preg_match('/Course: (.*?) \|/', $docRequest->remarks, $matches)) {
            $course = strtoupper($matches[1]);
        }
        if (preg_match('/Grad Date: (.*?)$/', $docRequest->remarks, $matches)) {
            $date = date('F d, Y', strtotime($matches[1]));
        }

        $qrContext = [
            'qrRequestId' => $docRequest->id,
            'qrHolderIdentifier' => $docRequest->student_number,
            'qrIssuedAt' => $docRequest->updated_at,
        ];
        $documentQr = app(DocumentQrCode::class)->make('Diploma', $name, [
            'request_id' => $docRequest->id,
            'holder_identifier' => $docRequest->student_number,
            'issued_at' => $docRequest->updated_at,
            'fields' => ['course' => $course, 'graduation_date' => $date],
        ]);
        $html = view('diploma.pdf-template', compact('name', 'course', 'date', 'qrContext', 'documentQr'))->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = "Diploma_{$name}.pdf";
        $bytes = $dompdf->output();
        app(DocumentQrCode::class)->registerArtifact($documentQr['document'], $bytes, $filename, 'application/pdf');

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
