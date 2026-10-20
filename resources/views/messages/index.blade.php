@extends('layouts.app')

@section('title', 'Message Hub')
@section('header', auth()->user()->role === 'registrar' ? 'Registrar Message Hub' : 'Message the Registrar')

@section('content')
<div class="max-w-7xl mx-auto space-y-5">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-bold">Please check your message.</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-[360px_minmax(0,1fr)]">
        <aside class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 p-5">
                <h2 class="text-lg font-extrabold text-[#000638]">Conversations</h2>
                <p class="mt-1 text-sm text-slate-500">{{ auth()->user()->role === 'registrar' ? 'Student questions and messages' : 'Your private messages with the Registrar' }}</p>
            </div>

            @if(auth()->user()->role === 'student')
            <details class="border-b border-slate-200 p-4" {{ !$selected && $threads->isEmpty() ? 'open' : '' }}>
                <summary class="cursor-pointer list-none rounded-xl bg-[#000638] px-4 py-3 text-center text-sm font-bold text-white">+ New message</summary>
                <form method="POST" action="{{ route('messages.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="subject" class="mb-1 block text-sm font-bold text-slate-700">Subject</label>
                        <input id="subject" name="subject" value="{{ old('subject') }}" maxlength="160" required placeholder="What is your question about?" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="new_message" class="mb-1 block text-sm font-bold text-slate-700">Message description</label>
                        <textarea id="new_message" name="message" rows="5" maxlength="5000" required placeholder="Write the complete details of your question..." class="w-full resize-y rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('message') }}</textarea>
                    </div>
                    <button class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">Send to Registrar</button>
                </form>
            </details>
            @endif

            <div class="max-h-[620px] overflow-y-auto divide-y divide-slate-100">
                @forelse($threads as $thread)
                    <a href="{{ route('messages.index', ['thread' => $thread->id]) }}" class="block p-4 transition hover:bg-indigo-50 {{ $selected?->id === $thread->id ? 'bg-indigo-50 border-l-4 border-indigo-600' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <p class="truncate text-sm font-extrabold text-[#000638]">{{ $thread->subject }}</p>
                            @if($thread->unread_count)<span class="shrink-0 rounded-full bg-red-500 px-2 py-0.5 text-xs font-bold text-white">{{ $thread->unread_count }}</span>@endif
                        </div>
                        @if(auth()->user()->role === 'registrar')<p class="mt-1 truncate text-xs font-semibold text-indigo-700">{{ $thread->student->display_name }}</p>@endif
                        <p class="mt-1 truncate text-xs text-slate-500">{{ $thread->latestMessage?->body }}</p>
                        <p class="mt-2 text-[11px] text-slate-400">{{ $thread->last_message_at?->format('M d, Y · h:i A') }}</p>
                    </a>
                @empty
                    <p class="p-6 text-center text-sm text-slate-500">No conversations yet.</p>
                @endforelse
            </div>
            @if($threads->hasPages())<div class="border-t border-slate-200 p-3">{{ $threads->links() }}</div>@endif
        </aside>

        <section class="min-h-[600px] rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            @if($selected)
                <header class="border-b border-slate-200 bg-[#000638] p-5 text-white">
                    <p class="text-xs font-bold uppercase tracking-wider text-[#ffd22d]">Subject</p>
                    <h2 class="mt-1 text-xl font-extrabold">{{ $selected->subject }}</h2>
                    @if(auth()->user()->role === 'registrar')
                        <p class="mt-2 text-sm text-slate-200">Student: <strong class="text-white">{{ $selected->student->display_name }}</strong> · {{ $selected->student->student_number ?: 'No student number' }}</p>
                    @else
                        <p class="mt-2 text-sm text-slate-200">Private conversation with the Registrar</p>
                    @endif
                </header>
                <div class="max-h-[480px] space-y-4 overflow-y-auto bg-slate-50 p-5" id="message-list">
                    @foreach($selected->messages as $message)
                        @php($mine = $message->sender_id === auth()->id())
                        <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                            <article class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm {{ $mine ? 'bg-indigo-600 text-white rounded-br-sm' : 'border border-slate-200 bg-white text-slate-800 rounded-bl-sm' }}">
                                <p class="mb-1 text-xs font-bold {{ $mine ? 'text-indigo-100' : 'text-indigo-700' }}">{{ $mine ? 'You' : ($message->sender->role === 'registrar' ? 'Registrar' : $message->sender->display_name) }}</p>
                                <p class="whitespace-pre-wrap break-words text-sm leading-relaxed">{{ $message->body }}</p>
                                <p class="mt-2 text-[11px] {{ $mine ? 'text-indigo-200' : 'text-slate-400' }}">{{ $message->created_at->format('M d, Y · h:i A') }}</p>
                            </article>
                        </div>
                    @endforeach
                </div>
                <form method="POST" action="{{ route('messages.reply', $selected) }}" class="border-t border-slate-200 p-5">
                    @csrf
                    <label for="reply_message" class="mb-2 block text-sm font-bold text-slate-700">Reply</label>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <textarea id="reply_message" name="message" rows="3" maxlength="5000" required placeholder="Type your reply..." class="min-h-[86px] flex-1 resize-y rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <button class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white hover:bg-indigo-700">Send Reply</button>
                    </div>
                </form>
            @else
                <div class="flex min-h-[600px] flex-col items-center justify-center px-6 text-center">
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 20l1.3-3.47A7.39 7.39 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <h2 class="text-xl font-extrabold text-[#000638]">Select a conversation</h2>
                    <p class="mt-2 max-w-md text-sm text-slate-500">{{ auth()->user()->role === 'student' ? 'Open an existing conversation or create a new message for the Registrar.' : 'Choose a student conversation from the list to read and reply.' }}</p>
                </div>
            @endif
        </section>
    </div>
</div>
@if($selected)<script>document.addEventListener('DOMContentLoaded',()=>{const list=document.getElementById('message-list');if(list)list.scrollTop=list.scrollHeight;});</script>@endif
@endsection
