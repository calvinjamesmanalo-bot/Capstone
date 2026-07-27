<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-5">
        <div>
            <h3 class="text-lg font-bold text-slate-900">{{ $title }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $accent === 'emerald' ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700' }}">
            {{ $users->count() }} {{ \Illuminate\Support\Str::plural('account', $users->count()) }}
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[780px] text-left">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-6 py-4">Account</th>
                    @if($isStudentTable)
                        <th class="px-6 py-4">Student Number</th>
                    @else
                        <th class="px-6 py-4">Role</th>
                    @endif
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Last Updated</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                    @php
                        $initials = collect(preg_split('/\s+/', trim($user->display_name)))
                            ->filter()
                            ->map(fn ($name) => strtoupper(substr($name, 0, 1)))
                            ->take(2)
                            ->join('');
                    @endphp
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl font-bold {{ $accent === 'emerald' ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700' }}">
                                    {{ $initials ?: '?' }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">{{ $user->display_name }}</p>
                                    <p class="mt-0.5 truncate text-sm text-slate-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            @if($isStudentTable)
                                <span class="font-mono text-sm font-semibold text-slate-700">{{ $user->student_number ?: 'Not assigned' }}</span>
                            @else
                                <span class="inline-flex rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold capitalize text-slate-700">
                                    {{ str_replace('_', ' ', $user->role ?? 'User') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            <span class="inline-flex items-center gap-2 text-sm font-medium text-emerald-700">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                Active
                            </span>
                        </td>
                        <td class="px-6 py-5 text-sm text-slate-500">
                            {{ $user->updated_at?->diffForHumans() ?? '—' }}
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('users.edit', $user) }}" title="Edit {{ $user->display_name }}" class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Delete {{ addslashes($user->display_name) }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Delete {{ $user->display_name }}" class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">
                            No {{ strtolower($title) }} found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
