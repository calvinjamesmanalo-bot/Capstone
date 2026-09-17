@extends('layouts.app')

@section('page_title', 'Notifications')
@section('page_subtitle', 'Updates about your document requests')

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <div class="flex items-center justify-between gap-3">
        <h3 class="text-lg font-bold text-[#000638]">Request updates</h3>
        <form method="POST" action="{{ route('student.notifications.read-all') }}">
            @csrf
            <button type="submit" class="rounded-lg bg-[#000638] px-4 py-2 text-sm font-semibold text-white">Mark all as read</button>
        </form>
    </div>

    @forelse($notifications as $notification)
        <div class="rounded-xl border p-4 {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-amber-300 bg-amber-50' }}">
            <p class="font-medium text-slate-900">{{ $notification->data['message'] ?? 'Request update' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $notification->created_at->diffForHumans() }}</p>
            <div class="mt-3 flex gap-4 text-sm">
                <a class="font-semibold text-blue-700 underline" href="{{ route('student.my-requests') }}">View requests</a>
                @if(! $notification->read_at)
                    <form method="POST" action="{{ route('student.notifications.read', $notification->id) }}">
                        @csrf
                        <button class="font-semibold text-slate-700 underline" type="submit">Mark as read</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <p class="rounded-xl border border-slate-200 bg-white p-6 text-slate-600">No notifications yet.</p>
    @endforelse

    {{ $notifications->links() }}
</div>
@endsection
