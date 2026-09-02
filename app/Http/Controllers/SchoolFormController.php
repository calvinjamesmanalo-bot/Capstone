<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SchoolFormController extends Controller
{
    public function home(Request $request)
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['admin', 'records_officer'], true), 403);

        return view('school-forms.home');
    }
}
