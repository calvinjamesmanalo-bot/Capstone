@php
    $gradeText = $student['grade_section'] !== '' ? $student['grade_section'] : $student['grade_level'];
    $templateImage = $assets['template'] ?? null;
    $sealImage = $assets['seal'] ?? null;
@endphp

<section class="certificate-page certificate-{{ $form['certificate_type'] }} @if ($templateImage) has-template-image @endif">
    @if ($templateImage)
        <img class="certificate-template-layer" src="{{ $templateImage }}" alt="">
    @else
        <span class="corner corner-navy-top"></span>
        <span class="corner corner-yellow-stroke-top"></span>
        <span class="corner corner-yellow-top"></span>
        <span class="corner corner-gray-top"></span>
        <span class="corner corner-navy-bottom"></span>
        <span class="corner corner-yellow-stroke-bottom"></span>
        <span class="corner corner-yellow-bottom"></span>
        <span class="corner corner-gray-bottom"></span>

        <div class="school-rule"></div>
    @endif

    <div class="certificate-inner">
        <header class="school-header">
            <div class="school-brand" aria-label="Fiat Lux Academe Cavite">
                <div class="school-name">FIAT LUX ACADEME</div>
                <div class="school-location">Cavite</div>
            </div>

            <div class="school-seal">
                @if ($sealImage)
                    <img src="{{ $sealImage }}" alt="Fiat Lux Academe seal">
                @else
                    <div class="seal-fallback" aria-label="Fiat Lux Academe seal placeholder">
                        <span class="seal-ring seal-ring-top">FIAT LUX ACADEME</span>
                        <span class="seal-ring seal-ring-bottom">CAVITE</span>
                        <span class="seal-center">FLA</span>
                        <span class="seal-year">1993</span>
                    </div>
                @endif
            </div>
        </header>

        <h1 class="certificate-title">{{ $certificate['title'] }}</h1>

        <main class="certificate-body">
            @switch($form['certificate_type'])
                @case('enrollment')
                    <p>
                        This is to certify that <strong>{{ $student['name'] }}</strong> is presently enrolled in this
                        institution as {{ $gradeText }} student for Academic Year {{ $student['school_year'] }}.
                    </p>
                    @if ($form['purpose'] !== '')
                        <p>This certification is being issued for {{ $form['purpose'] }} only.</p>
                    @endif
                    <p>
                        This certification is being issued for whatever legal purpose it may serve him/her under the
                        official seal of the school this <strong>{{ $form['formatted_issue_date'] }}</strong>.
                    </p>
                    @break

                @case('completion')
                    <p>
                        This is to certify that <strong>{{ $student['name'] }}</strong> completed the academic
                        requirements in this institution as {{ $gradeText }} for Academic Year
                        {{ $student['school_year'] }}.
                    </p>
                    <p>
                        This certification is being issued for whatever legal purpose it may serve him/her under the
                        official seal of the school, this <strong>{{ $form['formatted_issue_date'] }}</strong>.
                    </p>
                    @break

                @case('good_moral')
                    <p>
                        This is to certify that <strong>{{ $student['name'] }}</strong> was a {{ $gradeText }}
                        student and graduated in this institution for the Academic Year
                        {{ $student['school_year'] }}.
                    </p>
                    <p>
                        This is to certify further that the aforementioned student is a person of good character and has
                        no record of any misbehavior.
                    </p>
                    <p>
                        This certification is being issued for whatever legal purpose it may serve him/her under the
                        official seal of the school on this <strong>{{ $form['formatted_issue_date'] }}</strong>.
                    </p>
                    @break

                @case('recognition')
                    <p>
                        This is to certify that <strong>{{ $student['name'] }}</strong> is a {{ $gradeText }}
                        student in this institution for Academic Year
                        {{ $student['school_year'] }}.
                    </p>
                    <p>
                        Furthermore, this is to certify that he/she is a {{ $form['recognition'] }}
                        for the academic year {{ $student['school_year'] }}.
                    </p>
                    <p>
                        This certification is being issued for whatever legal purpose it may serve him/her under the
                        official seal of the school this <strong>{{ $form['formatted_issue_date'] }}</strong>.
                    </p>
                    @break
            @endswitch
        </main>

        @if ($form['certificate_type'] === 'recognition')
            <div class="signed-label">Signed:</div>
            <div class="recognition-signatures">
                <div class="recognition-signature-left">
                    <p class="signature-name small-signature">RONELYN C. MEJORADA<br>ESPORAS</p>
                    <p class="signature-position">FLA-SSC, Adviser<br>Coordinator</p>
                </div>
                <div class="recognition-signature-right">
                    <p class="signature-name small-signature">JIMMY E.</p>
                    <p class="signature-position">Student Affairs</p>
                </div>
            </div>
            <div class="principal-signature">
                <p class="signature-name small-signature">{{ $certificate['signatory'] }}</p>
                <p class="signature-position">{{ $certificate['position'] }}</p>
            </div>
        @else
            <div class="signature-block">
                <div>
                    <p class="signature-name">{{ $certificate['signatory'] }}</p>
                    <p class="signature-position">{{ $certificate['position'] }}</p>
                </div>
            </div>
        @endif
    </div>

    <div class="seal-note">Not Valid<br>Without<br>School Seal</div>
</section>
