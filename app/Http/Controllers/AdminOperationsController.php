<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AdminOperationsController extends Controller
{
    public function backup()
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        abort_if(Artisan::call('backup:create', ['--name' => 'admin']) !== 0, 500, 'Backup creation failed.');

        return back()->with('success', 'System backup created successfully.');
    }

    public function downloadBackup(string $file)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        abort_unless($file === basename($file) && str_ends_with($file, '.zip'), 404);
        $path = storage_path('app/backups'.DIRECTORY_SEPARATOR.$file);
        abort_unless(File::isFile($path), 404);

        return response()->download($path, $file, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function testEmail(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);
        $data = $request->validate(['test_email' => ['required', 'email', 'max:255']]);
        $mailer = (string) config('mail.default');
        $username = (string) config("mail.mailers.{$mailer}.username");
        $password = (string) config("mail.mailers.{$mailer}.password");
        if ($mailer === 'smtp' && ($username === '' || $password === '' || preg_match('/your-|example|change[-_ ]?me/i', $username))) {
            return back()->withErrors([
                'test_email' => 'SMTP is not configured yet. Replace the placeholder MAIL_USERNAME and MAIL_PASSWORD, then clear the configuration cache.',
            ])->withInput();
        }

        try {
            Mail::raw('This is a test message from the administrator system settings.', fn ($message) => $message
                ->to($data['test_email'])->subject('System email test'));
        } catch (Throwable $exception) {
            Log::warning('Administrator test email failed.', ['exception' => $exception::class]);

            return back()->withErrors([
                'test_email' => 'The email service rejected the test message. Check the SMTP username, app password, host, and port.',
            ])->withInput();
        }

        return back()->with('success', 'Test email was handed to the configured mail service.');
    }
}
