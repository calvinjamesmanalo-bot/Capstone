<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\RequestDocument;
use Illuminate\Http\Request;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

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
            'remarks' => $request->purpose ? "Purpose: {$request->purpose}" : $docRequest->remarks
        ]);

        record_log('Submitted Good Moral to Registrar', 'Requests', "Request #{$docRequest->id} submitted for approval");

        return redirect()->route('requests.index')->with('success', 'Good Moral has been submitted to Registrar for approval.');
    }

    public function previewFromRequest($id)
    {
        $docRequest = RequestDocument::with('student')->findOrFail($id);
        
        if (!$docRequest->student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }

        $name = $docRequest->student->name;
        $date = $docRequest->created_at->format('jS \d\a\y \o\f F, Y');
        
        // Extract purpose from remarks
        $purpose = 'any legal purpose it may serve';
        if (str_starts_with($docRequest->remarks, 'Purpose: ')) {
            $purpose = substr($docRequest->remarks, 9);
        }

        $html = view('good-moral.pdf-template', compact('name', 'date', 'purpose'))->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream("Good_Moral_{$name}.pdf", ["Attachment" => false]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'student_name' => 'required|string',
            'checklist' => 'required|array|size:4', // Siguraduhin na lahat ng checklist ay chineck
        ], [
            'checklist.required' => 'Dapat i-check lahat ng requirements bago mag-generate.',
            'checklist.size' => 'Dapat i-check lahat ng requirements bago mag-generate.',
        ]);

        $name = $request->student_name;
        $date = now()->format('jS \d\a\y \o\f F, Y');
        $purpose = $request->purpose ?? 'any legal purpose it may serve';

        $html = view('good-moral.pdf-template', compact('name', 'date', 'purpose'))->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        if ($request->has('preview')) {
            return $dompdf->stream("Good_Moral_{$name}.pdf", ["Attachment" => false]);
        }

        return $dompdf->stream("Good_Moral_{$name}.pdf");
    }
}
