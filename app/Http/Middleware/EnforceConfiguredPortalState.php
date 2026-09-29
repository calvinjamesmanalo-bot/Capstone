<?php

namespace App\Http\Middleware;

use App\Support\SystemContent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnforceConfiguredPortalState
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('settings')) {
            return $next($request);
        }

        $role = $request->user()?->role;
        $route = (string) optional($request->route())->getName();
        $adminAccess = $role === 'admin' || str_starts_with($route, 'login.admin') || $route === 'logout';
        if (SystemContent::enabled('maintenance_mode', false) && ! $adminAccess) {
            abort(503, SystemContent::get('maintenance_message', 'The portal is temporarily unavailable for maintenance.'));
        }

        $rules = [
            'student_registration_enabled' => ['register.', 'student.register'],
            'document_requests_enabled' => ['student.request'],
            'grade_uploads_enabled' => ['grade-portal.upload', 'school-forms.grade-sheets.store', 'school-forms.grade-sheets.auto-batch'],
            'public_verification_enabled' => ['documents.verify', 'documents.authenticity'],
        ];
        foreach ($rules as $setting => $prefixes) {
            if (! SystemContent::enabled($setting, true) && collect($prefixes)->contains(fn ($prefix) => str_starts_with($route, $prefix))) {
                abort(503, 'This portal feature is currently disabled by the administrator.');
            }
        }

        return $next($request);
    }
}
