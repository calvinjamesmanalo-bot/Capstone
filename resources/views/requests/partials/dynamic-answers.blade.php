@if(!empty($req->form_snapshot['fields']))
<details class="my-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
    <summary class="cursor-pointer font-semibold">Submitted form details</summary>
    <dl class="mt-3 space-y-3">
        @foreach($req->form_snapshot['fields'] ?? [] as $field)
        @php($answer = $req->dynamic_values[$field['key']] ?? null)
        <div><dt class="font-semibold">{{ $field['label'] }}</dt><dd class="whitespace-pre-line break-words text-slate-600">@if($field['type'] === 'file' && is_array($answer))<a class="text-blue-700 underline" href="{{ route('requests.dynamic-attachment', [$req, $field['key']]) }}">{{ $answer['name'] }}</a>@elseif($field['type'] === 'checkbox'){{ $answer ? 'Yes' : 'No' }}@else{{ $answer ?? 'Not provided' }}@endif</dd></div>
        @endforeach
    </dl>
</details>
@endif
