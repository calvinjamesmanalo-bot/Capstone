@php
    $content = \App\Models\Setting::whereIn('key', ['school_address', 'contact_number', 'mobile_number', 'system_email', 'office_hours'])->pluck('value', 'key');
@endphp
<section aria-labelledby="school-contact-heading" class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 id="school-contact-heading" class="text-lg font-semibold text-slate-900">School Contact Information</h2>
    <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
        @foreach(['school_address' => 'School Address', 'contact_number' => 'Landline Number', 'mobile_number' => 'Mobile/Cellphone Number', 'system_email' => 'System Email', 'office_hours' => 'Office Hours'] as $key => $label)
            <div class="min-w-0">
                <dt class="font-semibold text-slate-800">{{ $label }}</dt>
                <dd class="mt-1 whitespace-pre-line break-words text-slate-600">{{ $content->get($key) ?: 'Not provided' }}</dd>
            </div>
        @endforeach
    </dl>
</section>
