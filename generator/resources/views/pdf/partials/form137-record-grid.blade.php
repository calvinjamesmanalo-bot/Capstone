@php
$fixedAreas = [
    ['Mother Tongue'], ['Filipino'], ['English'], ['Mathematics'], ['Science'], ['Araling Panlipunan','Makabansa'],
    ['EPP / TLE'], ['MAPEH'], ['Music', null, true], ['Arts', null, true], ['Physical Education', null, true],
    ['Health', null, true], ['Eduk. sa Pagpapakatao','Good Manners and Right Conduct'],
    ['*Arabic Language'], ['*Islamic Values Education'],
];
@endphp
<table class="records"><tbody>
@foreach(collect($pageRecords)->chunk(2) as $pair)
<tr>
@foreach($pair as $record)
<td><div class="record">
    <table class="meta">
        <tr><td style="width:72%">School: <span class="write" style="width:78%"></span></td><td>School ID: <span class="write" style="width:47%"></span></td></tr>
        <tr><td>District: <span class="write" style="width:38%"></span> Division: <span class="write" style="width:28%"></span></td><td>Region: <span class="write" style="width:63%"></span></td></tr>
        <tr><td>Classified as Grade: <span class="write" style="width:12%">{{ $record['level'] ?? '' }}</span> Section: <span class="write" style="width:35%">{{ $record['section'] ?? '' }}</span></td><td>School Year: <span class="write" style="width:53%">{{ $record['school_year'] ?? '' }}</span></td></tr>
        <tr><td>Name of Adviser/Teacher: <span class="write" style="width:58%"></span></td><td>Signature: <span class="write" style="width:61%"></span></td></tr>
    </table>
    <table class="grade"><thead><tr><th rowspan="2" class="area">LEARNING AREAS</th><th colspan="4">Quarterly Rating</th><th rowspan="2" class="final">Final<br>Rating</th><th rowspan="2" class="remarks">Remarks</th></tr><tr><th class="q">1</th><th class="q">2</th><th class="q">3</th><th class="q">4</th></tr></thead><tbody>
    @foreach($fixedAreas as $definition)
        @php
            $key = strtolower($definition[0]); $alias = isset($definition[1]) ? strtolower((string)$definition[1]) : null;
            $area = $record ? (($record['areas_by_name'][$key] ?? null) ?: ($alias ? ($record['areas_by_name'][$alias] ?? null) : null)) : null;
        @endphp
        <tr><td @class(['area', 'sub' => ($definition[2] ?? false)])>{{ $definition[0] }}</td>@for($q=1;$q<=4;$q++)<td>{{ data_get($area, "quarters.$q") !== null ? number_format(data_get($area, "quarters.$q"), 0) : '' }}</td>@endfor<td>{{ data_get($area, 'final') }}</td><td>{{ data_get($area, 'remarks') }}</td></tr>
    @endforeach
    <tr class="general"><td class="area">General Average</td><td></td><td></td><td></td><td></td><td>{{ $record['general_average'] ?? '' }}</td><td>{{ $record['remarks'] ?? '' }}</td></tr>
    </tbody></table>
    <div class="remedial-block">
        <table class="remedial-title"><tr><td style="width:32%">Remedial Classes</td><td>Date Conducted: <span class="write" style="width:24%"></span> to <span class="write" style="width:24%"></span></td></tr></table>
        <table class="remedial">
            <tr><th style="width:32%">Learning Areas</th><th style="width:16%">Final<br>Rating</th><th style="width:18%">Remedial Class<br>Mark</th><th style="width:17%">Recomputed Final<br>Grade</th><th style="width:17%">Remarks</th></tr>
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
        </table>
    </div>
</div></td>
@endforeach
@if($pair->count() === 1)<td></td>@endif
</tr>
@endforeach
</tbody></table>
