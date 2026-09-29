@extends('layouts.app')
@section('title', 'Manage School Forms')
@section('page_title', 'Manage School Forms')
@section('page_subtitle', 'Edit School Forms availability and document prices')

@section('content')
    <div class="space-y-6">
        @if($errors->any())
            <ul role="alert" class="rounded-xl bg-red-50 p-5 text-red-800">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            {{-- HEADER --}}
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-5 py-5">
                <div class="flex items-center gap-4">
                    <div class="flex h-10 w-10 items-center justify-center text-[#000638]">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Request Forms</h2>
                        <p class="mt-1 text-sm text-slate-500">Manage current and archived request forms.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3" aria-label="Request form lists">
                    <a href="{{ route('request-types.index') }}" @if(!$showArchived) aria-current="page" @endif class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium {{ !$showArchived ? 'bg-[#000638] text-white' : 'bg-white text-slate-700' }}">Current Forms</a>
                    <a href="{{ route('request-types.index', ['view' => 'archived']) }}" @if($showArchived) aria-current="page" @endif class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium {{ $showArchived ? 'bg-[#000638] text-white' : 'bg-white text-slate-700' }}">Archived Forms</a>
                </div>
            </div>

            {{-- TABLE --}}
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        @foreach(['School Form Name', 'Price', 'Type', 'Status', 'Actions'] as $heading)
                            <th scope="col" class="px-5 py-4 font-semibold">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">

                    {{-- BUILT-IN FORMS --}}
                    @foreach($builtIns as $name => $entry)
                    <tr class="group/row transition-colors hover:bg-[#000638]/[0.05]">
                        <th scope="row" class="border-l-2 border-transparent px-5 py-4 font-semibold text-slate-800 transition-colors group-hover/row:border-[#000638]">{{ $name }}</th>

                        <td class="whitespace-nowrap px-5 py-4">
                            <div class="group/price inline-flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-slate-100 text-xs font-bold text-[#000638]">₱</span>
                                <span class="font-medium text-slate-700">{{ number_format($entry['fee'], 2) }}</span>

                                <details class="price-editor relative" @if(old('price_key') === $entry['key']) open @endif>
                                    <summary title="Edit price" class="price-trigger flex h-7 w-7 cursor-pointer list-none items-center justify-center rounded-md border border-slate-200 bg-white text-slate-400 opacity-0 transition group-hover/price:opacity-100 hover:border-[#000638]/20 hover:bg-[#000638]/[0.05] hover:text-[#000638]">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-8.5a2.121 2.121 0 013 3L12 14l-4 1 1-4 6.5-6.5z"/></svg>
                                    </summary>

                                    <div class="price-popup fixed z-50 w-64 rounded-xl border border-slate-200 bg-white p-4 shadow-lg">
                                        <button type="button" onclick="this.closest('.price-editor').removeAttribute('open')" title="Close" class="absolute right-3 top-3 flex h-6 w-6 items-center justify-center rounded-md text-slate-400 transition hover:bg-[#000638]/[0.05] hover:text-[#000638]">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>

                                        <form method="POST" action="{{ route('request-types.built-in.price', $entry['key']) }}" class="space-y-3">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="price_key" value="{{ $entry['key'] }}">
                                            <div class="pr-7"><label for="{{ $entry['key'] }}" class="block text-sm font-semibold text-slate-800">Edit Price</label><p class="mt-1 text-xs text-slate-500">{{ $name }}</p></div>
                                            <div class="relative"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">₱</span><input id="{{ $entry['key'] }}" name="fee" type="number" min="0" max="999999.99" step="0.01" required value="{{ old('price_key') === $entry['key'] ? old('fee') : $entry['fee'] }}" class="w-full rounded-lg border-slate-300 pl-8 text-sm focus:border-[#000638] focus:ring-[#000638]"></div>
                                            <button type="submit" class="w-full rounded-lg bg-[#000638] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#10175c]">Save Price</button>
                                        </form>
                                    </div>
                                </details>
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-700">Built-in</td>

                        <td class="px-5 py-4">
                            @if($entry['enabled'])
                                <span class="inline-flex items-center gap-1.5 font-medium text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Enabled</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 font-medium text-red-600"><span class="h-2 w-2 rounded-full bg-red-500"></span>Disabled</span>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <form method="POST" action="{{ route('request-types.built-in.availability', $entry['key']) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="enabled" value="{{ $entry['enabled'] ? 0 : 1 }}">
                                @if($entry['enabled'])
                                    <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">Disable</button>
                                @else
                                    <button type="submit" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">Enable</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                    @endforeach

                    {{-- CUSTOM FORMS --}}
                    @foreach($types as $type)
                    <tr class="group/row transition-colors hover:bg-[#000638]/[0.05]">
                        <th scope="row" class="border-l-2 border-transparent px-5 py-4 font-semibold text-slate-800 transition-colors group-hover/row:border-[#000638]">{{ $type->name }}</th>

                        <td class="whitespace-nowrap px-5 py-4">
                            <div class="group/price inline-flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-slate-100 text-xs font-bold text-[#000638]">₱</span>
                                <span class="font-medium text-slate-700">{{ number_format($type->fee, 2) }}</span>

                                @unless($type->trashed())
                                <details class="price-editor relative">
                                    <summary title="Edit price" class="price-trigger flex h-7 w-7 cursor-pointer list-none items-center justify-center rounded-md border border-slate-200 bg-white text-slate-400 opacity-0 transition group-hover/price:opacity-100 hover:border-[#000638]/20 hover:bg-[#000638]/[0.05] hover:text-[#000638]">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-8.5a2.121 2.121 0 013 3L12 14l-4 1 1-4 6.5-6.5z"/></svg>
                                    </summary>

                                    <div class="price-popup fixed z-50 w-64 rounded-xl border border-slate-200 bg-white p-4 shadow-lg">
                                        <button type="button" onclick="this.closest('.price-editor').removeAttribute('open')" title="Close" class="absolute right-3 top-3 flex h-6 w-6 items-center justify-center rounded-md text-slate-400 transition hover:bg-[#000638]/[0.05] hover:text-[#000638]">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>

                                        <form method="POST" action="{{ route('request-types.update', $type) }}" class="space-y-3">
                                            @csrf @method('PATCH')
                                            <div class="pr-7"><label for="custom-fee-{{ $type->id }}" class="block text-sm font-semibold text-slate-800">Edit Price</label><p class="mt-1 text-xs text-slate-500">{{ $type->name }}</p></div>
                                            <div class="relative"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">₱</span><input id="custom-fee-{{ $type->id }}" name="fee" type="number" min="0" max="999999.99" step="0.01" required value="{{ $type->fee }}" class="w-full rounded-lg border-slate-300 pl-8 text-sm focus:border-[#000638] focus:ring-[#000638]"></div>
                                            <button type="submit" class="w-full rounded-lg bg-[#000638] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#10175c]">Save Price</button>
                                        </form>
                                    </div>
                                </details>
                                @endunless
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-700">Custom</td>

                        <td class="px-5 py-4">
                            @if($type->trashed())
                                <span class="inline-flex items-center gap-1.5 font-medium text-slate-500"><span class="h-2 w-2 rounded-full bg-slate-400"></span>Archived</span>
                            @elseif($type->is_active)
                                <span class="inline-flex items-center gap-1.5 font-medium text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Enabled</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 font-medium text-red-600"><span class="h-2 w-2 rounded-full bg-red-500"></span>Disabled</span>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            @unless($type->trashed())
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('request-types.toggle', $type) }}">
                                    @csrf @method('PATCH')
                                    @if($type->is_active)
                                        <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">Disable</button>
                                    @else
                                        <button type="submit" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">Enable</button>
                                    @endif
                                </form>

                                <form method="POST" action="{{ route('request-types.destroy', $type) }}" onsubmit="return confirm('Archive this form? Existing requests will remain available.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-slate-300 bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">Archive</button>
                                </form>

                                <button type="button" data-edit-dialog="edit-request-type-{{ $type->id }}" aria-haspopup="dialog" aria-controls="edit-request-type-{{ $type->id }}" aria-label="Edit {{ $type->name }}" title="Edit form" class="flex h-8 w-8 items-center justify-center rounded-lg border border-[#000638]/20 bg-[#000638]/[0.05] text-[#000638] transition hover:bg-[#000638]/10">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-8.5a2.121 2.121 0 013 3L12 14l-4 1 1-4 6.5-6.5z"/></svg>
                                </button>
                            </div>
                            @else
                                <span class="text-sm font-medium text-slate-500">Archived</span>
                            @endunless
                        </td>
                    </tr>
                    @endforeach

                    @if($showArchived && $types->isEmpty())
                        <tr><td colspan="5" class="px-5 py-6 text-center text-slate-500">No archived custom forms.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div id="add-custom-request-form" class="rounded-2xl border border-slate-200 bg-white">
           <div class="flex items-center gap-4 border-b border-slate-200 px-5 py-5">
                <div class="flex h-10 w-10 items-center justify-center text-[#000638]">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V6l-4-4z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2v4h4M12 11v6M9 14h6"/>
                    </svg>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Add School Form</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Custom school forms that students can request. These forms will be PREPARED MANUALLY and are NOT AUTOMATICALLY generated by the system.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('request-types.store') }}" class="p-5">
                @csrf
                <input type="hidden" name="_form" value="create-request-type">

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="new-request-name" class="block text-sm font-semibold text-slate-800">School Form Name</label>
                        <input id="new-request-name" name="name" type="text" maxlength="160" required value="{{ old('_form') === 'create-request-type' ? old('name') : '' }}" class="mt-2 w-full rounded-lg border-2 border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#000638] focus:ring-1 focus:ring-[#000638]">
                    </div>

                    <div>
                        <label for="new-request-price" class="block text-sm font-semibold text-slate-800">Price (PHP)</label>
                        <div class="relative mt-2">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-medium text-slate-500">₱</span>
                            <input id="new-request-price" name="fee" type="number" min="0" max="999999.99" step="0.01" required value="{{ old('_form') === 'create-request-type' ? old('fee') : 0 }}" class="w-full rounded-lg border-2 border-slate-300 py-2 pl-8 pr-3 text-sm outline-none transition focus:border-[#000638] focus:ring-1 focus:ring-[#000638]">
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="submit" class="rounded-lg bg-[#000638] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#10175c]">Add Form</button>
                </div>
            </form>
        </div>

    </div>

    @foreach($types as $type)
    @unless($type->trashed())
        @php($restoreEdit = $errors->any() && old('_form') === 'edit-request-type-'.$type->id)

        <dialog
            id="edit-request-type-{{ $type->id }}"
            style="position: fixed; top: 50%; left: 50%; right: auto; bottom: auto; transform: translate(-50%, -50%); margin: 0; width: 50vw; max-width: 800px; max-height: calc(100dvh - 2rem); overflow-y: auto;"
            class="request-type-dialog rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-black/40"
            aria-labelledby="edit-title-{{ $type->id }}"
            @if($restoreEdit) data-reopen @endif
        >
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-5">
                <div>
                    <h2 id="edit-title-{{ $type->id }}" class="text-xl font-semibold text-slate-900">
                        Edit Custom School Form
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Update the school form name and price.
                    </p>
                </div>

                <button
                    type="button"
                    data-close-dialog
                    aria-label="Close edit form"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-[#000638]/[0.05] hover:text-[#000638]"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-5">
                @if($restoreEdit)
                    <ul role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('request-types.update', $type) }}">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="_form" value="edit-request-type-{{ $type->id }}">
                    <input type="hidden" name="version" value="{{ $restoreEdit ? old('version') : $type->version }}">

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="edit-name-{{ $type->id }}" class="block text-sm font-semibold text-slate-800">
                                School Form Name
                            </label>

                            <input
                                id="edit-name-{{ $type->id }}"
                                name="name"
                                type="text"
                                maxlength="160"
                                required
                                autofocus
                                value="{{ $restoreEdit ? old('name') : $type->name }}"
                                class="mt-2 w-full rounded-lg border-2 border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#000638] focus:ring-1 focus:ring-[#000638]"
                            >
                        </div>

                        <div>
                            <label for="edit-fee-{{ $type->id }}" class="block text-sm font-semibold text-slate-800">
                                Price (PHP)
                            </label>

                            <div class="relative mt-2">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 font-medium text-slate-500">
                                    ₱
                                </span>

                                <input
                                    id="edit-fee-{{ $type->id }}"
                                    name="fee"
                                    type="number"
                                    min="0"
                                    max="999999.99"
                                    step="0.01"
                                    required
                                    value="{{ $restoreEdit ? old('fee') : $type->fee }}"
                                    class="w-full rounded-lg border-2 border-slate-300 py-2 pl-8 pr-3 text-sm outline-none transition focus:border-[#000638] focus:ring-1 focus:ring-[#000638]"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button
                            type="submit"
                            class="rounded-lg bg-[#000638] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#10175c]"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </dialog>
    @endunless
@endforeach

    <script>
        document.querySelectorAll('[data-edit-dialog]').forEach(button => {
            button.addEventListener('click', () => document.getElementById(button.dataset.editDialog).showModal());
        });
        document.querySelectorAll('.request-type-dialog').forEach(dialog => {
            dialog.querySelector('[data-close-dialog]').addEventListener('click', () => dialog.close());
            dialog.addEventListener('click', event => {
                if (event.target !== dialog) return;
                const bounds = dialog.getBoundingClientRect();
                if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
            });
            if (dialog.hasAttribute('data-reopen')) dialog.showModal();
        });

        function positionPricePopup(editor) {
            const trigger = editor.querySelector('.price-trigger'), popup = editor.querySelector('.price-popup');
            if (!trigger || !popup) return;

            const rect = trigger.getBoundingClientRect(), popupWidth = 256;
            let left = rect.right - popupWidth;
            if (left < 8) left = 8;
            if (left + popupWidth > window.innerWidth - 8) left = window.innerWidth - popupWidth - 8;

            popup.style.left = left + 'px';

            const popupHeight = popup.offsetHeight;
            let top = rect.bottom + 8;
            if (top + popupHeight > window.innerHeight - 8) top = rect.top - popupHeight - 8;
            if (top < 8) top = 8;
            popup.style.top = top + 'px';
        }

        document.querySelectorAll('.price-editor').forEach(editor => {
            editor.addEventListener('toggle', () => {
                if (!editor.open) return;
                document.querySelectorAll('.price-editor[open]').forEach(other => { if (other !== editor) other.removeAttribute('open'); });
                positionPricePopup(editor);
            });
        });

        document.addEventListener('click', event => {
            document.querySelectorAll('.price-editor[open]').forEach(editor => {
                if (!editor.contains(event.target)) editor.removeAttribute('open');
            });
        });

        window.addEventListener('resize', () => {
            document.querySelectorAll('.price-editor[open]').forEach(positionPricePopup);
        });
    </script>
@endsection
