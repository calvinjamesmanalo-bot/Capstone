<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certification Maker Sandbox</title>
    @include('certifications.partials.styles')
</head>
<body>
    <div class="module-shell">
        <header class="module-header">
            <p class="eyebrow">Sandbox Module</p>
            <h1 class="module-title">Certification Maker</h1>
            <p class="module-subtitle">
                Enter the student details, choose a certificate type, review the generated document, then download or print the PDF.
            </p>
        </header>

        <div class="module-grid">
            <main class="panel panel-pad form-panel">
                <h2 class="panel-title">Select Certificate Type and Generate Preview</h2>

                @if ($errors->any())
                    <div class="error-box">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('certifications.preview') }}">
                    @csrf

                    <div class="form-grid">
                        <div class="field-full">
                            <label for="certificate_type">Certificate Type</label>
                            <select id="certificate_type" name="certificate_type" required>
                                <option value="" disabled @selected(($form['certificate_type'] ?? '') === '')>Select certificate type</option>
                                @foreach ($certificateTypes as $key => $type)
                                    <option value="{{ $key }}" @selected(($form['certificate_type'] ?? '') === $key)>
                                        {{ $type['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="student_name">Student Name</label>
                            <input id="student_name" name="student_name" value="{{ $form['student_name'] ?? '' }}" required>
                        </div>

                        <div>
                            <label for="lrn">LRN</label>
                            <input id="lrn" name="lrn" value="{{ $form['lrn'] ?? '' }}" maxlength="12" inputmode="numeric">
                        </div>

                        <div>
                            <label for="grade_level">Grade Level</label>
                            <input id="grade_level" name="grade_level" value="{{ $form['grade_level'] ?? '' }}" required>
                        </div>

                        <div>
                            <label for="section">Section</label>
                            <input id="section" name="section" value="{{ $form['section'] ?? '' }}">
                        </div>

                        <div>
                            <label for="school_year">School Year</label>
                            <input id="school_year" name="school_year" value="{{ $form['school_year'] ?? '' }}" required>
                        </div>

                        <div>
                            <label for="issue_date">Issue Date</label>
                            <input id="issue_date" type="date" name="issue_date" value="{{ $form['issue_date'] ?? '' }}" required>
                        </div>

                        <div class="field-full">
                            <label for="purpose">Purpose</label>
                            <input id="purpose" name="purpose" value="{{ $form['purpose'] ?? '' }}">
                            <p class="help-text">Optional. If entered, it will appear as the specific purpose line.</p>
                        </div>

                        <div class="field-full">
                            <label for="recognition">Recognition Title</label>
                            <input id="recognition" name="recognition" value="{{ $form['recognition'] ?? '' }}">
                            <p class="help-text">Required only for Certificate of Recognition.</p>
                        </div>
                    </div>

                    <div class="actions">
                        <button class="btn btn-primary" type="submit">Generate Certificate Preview</button>
                    </div>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
