<table class="record-layout">
    @foreach($pageRecords->chunk($columns) as $recordRow)
        <tr>
            @foreach($recordRow as $record)
                <td style="width:{{ 100 / $columns }}%">
                    @include('school-forms.partials.f137-record-preview', ['record' => $record, 'profile' => $profile])
                </td>
            @endforeach
            @for($blank = $recordRow->count(); $blank < $columns; $blank++)
                <td style="width:{{ 100 / $columns }}%"></td>
            @endfor
        </tr>
    @endforeach
</table>
