@php
    $periodNumbers = \App\Support\AcademicPeriod::numbers($record['school_year']);
    $usesTerms = \App\Support\AcademicPeriod::usesTerms($record['school_year']);
@endphp
<article class="record-card">
    <table class="record-meta">
        <tr>
            <td style="width:70%">School: <strong>{{ $profile['school'] }}</strong></td>
            <td>School ID: <strong>{{ $profile['school_id'] }}</strong></td>
        </tr>
        <tr>
            <td>District: <strong>{{ $profile['district'] }}</strong> &nbsp; Division: <strong>{{ $profile['division'] }}</strong></td>
            <td>Region: <strong>{{ $profile['region'] }}</strong></td>
        </tr>
        <tr>
            <td>Classified as Grade: <strong>{{ $record['level'] }}</strong> &nbsp; Section: <strong>{{ $record['section'] }}</strong></td>
            <td>School Year: <strong>{{ $record['school_year'] }}</strong></td>
        </tr>
        <tr>
            <td>Name of Adviser/Teacher: <strong>{{ $record['adviser_name'] ?: 'Not found' }}</strong></td>
            <td>Signature: __________________</td>
        </tr>
    </table>
    <table class="grade-table">
        <thead>
            <tr>
                <th rowspan="2" class="area">LEARNING AREAS</th>
                <th colspan="{{ count($periodNumbers) }}">{{ $usesTerms ? 'Term Rating' : 'Quarterly Rating' }}</th>
                <th rowspan="2" class="final">Final<br>Rating</th>
                <th rowspan="2" class="remarks">Remarks</th>
            </tr>
            <tr>
                @foreach($periodNumbers as $period)<th class="quarter">{{ $period }}</th>@endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($record['areas'] as $area)
                <tr>
                    <th class="area" title="{{ $area['name'] }}">{{ $area['name'] }}</th>
                    @foreach($periodNumbers as $period)
                        <td>{{ $area['quarters'][$period] ?? '' }}</td>
                    @endforeach
                    <td><strong>{{ $area['final'] ?? '' }}</strong></td>
                    <td>{{ $area['remarks'] }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ count($periodNumbers) + 3 }}">No grades uploaded</td></tr>
            @endforelse
            <tr class="general">
                <th class="area">General Average</th>
                <td colspan="{{ count($periodNumbers) }}"></td>
                <td>{{ $record['general_average'] ?? '' }}</td>
                <td>{{ $record['remarks'] }}</td>
            </tr>
        </tbody>
    </table>
    <div class="remedial-title">REMEDIAL CLASSES &nbsp; &middot; &nbsp; Date Conducted: __________ to __________</div>
</article>
