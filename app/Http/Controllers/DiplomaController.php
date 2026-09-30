<?php

namespace App\Http\Controllers;

use App\Models\RequestDocument;
use Illuminate\Http\Request;

class DiplomaController extends Controller
{
    public function index(Request $request)
    {
        $docRequest = null;

        if ($request->has('request_id')) {
            $docRequest = RequestDocument::findOrFail($request->request_id);
            abort_unless(str_contains(strtolower($docRequest->document_type), 'diploma'), 404);
        }

        return view('diploma.index', compact('docRequest'));
    }

    public function submitToRegistrar(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:request_documents,id',
            'checklist' => ['required', 'array', 'size:4'],
            'checklist.*' => ['required', 'integer', 'distinct', 'in:0,1,2,3'],
        ]);

        $docRequest = RequestDocument::findOrFail($request->request_id);
        abort_unless(str_contains(strtolower($docRequest->document_type), 'diploma'), 404);

        $docRequest->update([
            'status' => 'processed',
            'remarks' => 'PHYSICAL COPY ONLY | For release according to the selected delivery method',
        ]);

        record_log('Processed physical diploma request', 'Requests', "Request #{$docRequest->id} cleared for physical-copy preparation");

        return redirect()->route('requests.index')->with('success', 'Physical diploma request has been forwarded for preparation and release.');
    }
}
