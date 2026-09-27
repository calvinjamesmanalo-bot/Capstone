<?php

namespace App\Http\Controllers;

use App\Models\RequestType;
use App\Models\Setting;
use App\Support\RequestCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RequestTypeController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['view' => ['nullable', Rule::in(['current', 'archived'])]]);
        $showArchived = ($data['view'] ?? 'current') === 'archived';

        return view('request-types.index', [
            'types' => ($showArchived ? RequestType::onlyTrashed() : RequestType::query())->orderBy('name')->get(),
            'builtIns' => $showArchived ? [] : RequestCatalog::builtIns(),
            'showArchived' => $showArchived,
        ]);
    }

    public function updateBuiltInPrice(Request $request, string $key)
    {
        $name = RequestCatalog::builtInName($key);
        abort_if($name === null, 404);
        $data = $request->validate(['fee' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2']]);
        Setting::updateOrCreate(['key' => $key], ['value' => number_format((float) $data['fee'], 2, '.', ''), 'group' => 'pricing']);
        record_log('Updated Document Price', 'Requests', "Updated price for {$name}");

        return redirect()->route('request-types.index')->with('success', "Price updated for {$name}.");
    }

    public function setBuiltInAvailability(Request $request, string $key)
    {
        $name = RequestCatalog::builtInName($key);
        abort_if($name === null, 404);
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        Setting::updateOrCreate(['key' => RequestCatalog::availabilityKey($key)], [
            'value' => $data['enabled'] ? '1' : '0', 'group' => 'request_availability',
        ]);
        record_log('Changed Document Availability', 'Requests', "Changed availability for {$name}");

        return redirect()->route('request-types.index')->with('success', "Availability updated for {$name}.");
    }

    public function store(Request $request)
    {
        $type = RequestType::create([...$this->validated($request), 'is_active' => true, 'fields' => []]);
        record_log('Created Request Form', 'Requests', "Created form #{$type->id}");

        return redirect()->route('request-types.index')->with('success', 'Request form created.');
    }

    public function update(Request $request, RequestType $requestType)
    {
        $data = $this->validated($request, $requestType);
        DB::transaction(function () use ($request, $requestType, $data) {
            $locked = RequestType::lockForUpdate()->findOrFail($requestType->id);
            if ((int) $request->input('version') !== $locked->version) {
                throw ValidationException::withMessages(['version' => 'This form changed in another session. Reload before editing.']);
            }
            $locked->update([...$data, 'version' => $locked->version + 1]);
        });
        record_log('Updated Request Form', 'Requests', "Updated form #{$requestType->id}");

        return redirect()->route('request-types.index')->with('success', 'Request form updated. Existing submissions are unchanged.');
    }

    public function destroy(RequestType $requestType)
    {
        DB::transaction(function () use ($requestType) {
            $type = RequestType::lockForUpdate()->findOrFail($requestType->id);
            $type->update(['is_active' => false, 'version' => $type->version + 1]);
            $type->delete();
        });
        record_log('Archived Request Form', 'Requests', "Archived form #{$requestType->id}");

        return redirect()->route('request-types.index')->with('success', 'Form archived. Existing requests are retained.');
    }

    public function toggle(RequestType $requestType)
    {
        DB::transaction(function () use ($requestType) {
            $type = RequestType::lockForUpdate()->findOrFail($requestType->id);
            $type->update(['is_active' => ! $type->is_active, 'version' => $type->version + 1]);
        });
        record_log('Changed Request Availability', 'Requests', "Changed availability of form #{$requestType->id}");

        return redirect()->route('request-types.index')->with('success', 'Request availability updated.');
    }

    private function validated(Request $request, ?RequestType $type = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160', Rule::unique('request_types')->ignore($type?->id), function ($attribute, $value, $fail) {
                if (in_array(mb_strtolower(trim($value)), array_map('mb_strtolower', array_keys(RequestCatalog::BUILT_INS)), true)) {
                    $fail('This name is reserved for an existing document type.');
                }
            }],
            'fee' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'version' => [$type ? 'required' : 'nullable', 'integer', 'min:1'],
        ]);
        unset($data['version']);

        return $data;
    }
}
