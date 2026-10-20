<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Verification Status - {{ \App\Support\SystemContent::schoolName() }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.responsive-foundation')
</head>
<body class="flex min-h-screen items-center justify-center bg-[#f6f7fb] p-4">
    <main class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-lg sm:p-8">
        <img src="{{ \App\Support\SystemContent::logoUrl() }}" alt="Fiat Lux Academe seal" class="mb-6 h-16 w-16 rounded-full border-2 border-[#ffd22d] object-contain">
        <h1 class="text-2xl font-bold text-[#000638]">Account verification status</h1>

        @if(!$student)
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">This private status link is invalid or has already been used.</div>
            <a href="{{ route('student.registration.request', ['restart' => 1]) }}" class="mt-6 block rounded-lg bg-[#000638] px-5 py-3 text-center font-bold text-white">Start a new verification</a>
        @elseif($expired)
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">This status link has expired. Please submit a new School ID verification.</div>
            <a href="{{ route('student.registration.request', ['restart' => 1]) }}" class="mt-6 block rounded-lg bg-[#000638] px-5 py-3 text-center font-bold text-white">Submit again</a>
        @else
            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="font-bold text-slate-900">{{ $student->name }}</p>
                <p class="text-sm text-slate-600">Student Number: {{ $student->student_number }}</p>
            </div>

            @if($student->account_verification_status === 'pending')
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-900">
                    <p class="font-bold">Wait for the Admin or Registrar to verify your account</p>
                    <p class="mt-2 text-sm leading-6">Your School ID and identity photo have been submitted for review. Once approved, we will send a Continue Account Creation link to your Gmail. No verification code is required.</p>
                </div>
            @elseif($student->account_verification_status === 'rejected')
                <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-5 text-red-800"><p class="font-bold">Verification rejected</p><p class="mt-2 text-sm"><strong>Reason:</strong> {{ $student->account_verification_notes ?: 'Please contact the Admin.' }}</p></div>
                <a href="{{ route('student.registration.request', ['restart' => 1]) }}" class="mt-5 block rounded-lg bg-[#000638] px-5 py-3 text-center font-bold text-white">Submit new photos</a>
            @elseif($student->account_verification_status === 'approved')
                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-900"><p class="font-bold">Verification approved</p><p class="mt-1 text-sm">Open the approval email sent to your Gmail and select Continue account creation.</p></div>
                <a href="{{ route('student.registration.continue', ['token' => $token]) }}" class="mt-5 block rounded-lg bg-emerald-600 px-5 py-3 text-center font-bold text-white">Continue account creation</a>
            @endif

            @if($student->account_verification_status !== 'pending')
            <div class="mt-6 rounded-xl border-2 border-dashed border-blue-300 bg-blue-50 p-4">
                <p class="text-sm font-bold text-blue-900">Save this private link</p>
                <p class="mt-1 text-xs text-blue-800">Bookmark this page or take a screenshot. Anyone with this link can view the verification status, so do not share it.</p>
                <button type="button" onclick="navigator.clipboard.writeText(window.location.href); this.textContent='Link copied'" class="mt-3 rounded-lg border border-blue-300 bg-white px-4 py-2 text-sm font-bold text-blue-900">Copy status link</button>
            </div>
            @endif
        @endif

        <a href="{{ route('login') }}" class="mt-6 block text-center text-sm font-semibold text-[#000638] hover:underline">Back to sign in</a>
    </main>
</body>
</html>
