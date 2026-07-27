<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $certificate['label'] }} Preview</title>
    @include('certifications.partials.styles')
</head>
<body>
    <div class="module-shell">
        <div class="preview-layout">
            <div class="panel preview-toolbar no-print">
                <div>
                    <p class="eyebrow">Review Certificate</p>
                    <h2>{{ $certificate['label'] }} for {{ $student['name'] }}</h2>
                </div>

                <div class="actions">
                    <a class="btn btn-secondary" href="{{ route('certifications.index') }}">Back / Edit</a>
                    <button class="btn btn-secondary" type="button" onclick="window.print()">Print Preview</button>

                    <form method="POST" action="{{ route('certifications.pdf') }}">
                        @csrf
                        @foreach (['certificate_type', 'student_name', 'lrn', 'grade_level', 'section', 'school_year', 'issue_date', 'purpose', 'recognition'] as $field)
                            <input type="hidden" name="{{ $field }}" value="{{ $form[$field] ?? '' }}">
                        @endforeach
                        <button class="btn btn-accent" type="submit" name="output" value="stream">Open PDF / Print</button>
                    </form>

                    <form method="POST" action="{{ route('certifications.pdf') }}">
                        @csrf
                        @foreach (['certificate_type', 'student_name', 'lrn', 'grade_level', 'section', 'school_year', 'issue_date', 'purpose', 'recognition'] as $field)
                            <input type="hidden" name="{{ $field }}" value="{{ $form[$field] ?? '' }}">
                        @endforeach
                        <button class="btn btn-primary" type="submit" name="output" value="download">Download PDF</button>
                    </form>
                </div>
            </div>

            <div class="certificate-wrap">
                @include('certifications.partials.certificate')
            </div>
        </div>
    </div>
</body>
</html>
