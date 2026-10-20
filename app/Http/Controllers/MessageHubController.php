<?php

namespace App\Http\Controllers;

use App\Models\MessageEntry;
use App\Models\MessageThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MessageHubController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Schema::hasTable('message_threads') && Schema::hasTable('message_entries'), 503, 'The Message Hub is being activated.');
        $user = $request->user();
        abort_unless(in_array($user->role, ['student', 'registrar'], true), 403);

        $query = MessageThread::query()
            ->with(['student', 'latestMessage.sender'])
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->whereNull('read_at')->where('sender_id', '!=', $user->id)]);

        if ($user->role === 'student') {
            $query->where('student_id', $user->id);
        }

        $threads = $query->orderByDesc('last_message_at')->paginate(20)->withQueryString();
        $selected = null;

        if ($request->filled('thread')) {
            $selected = MessageThread::with(['student', 'messages.sender'])->findOrFail($request->integer('thread'));
            $this->authorizeThread($selected, $user);
            MessageEntry::where('thread_id', $selected->id)
                ->where('sender_id', '!=', $user->id)->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return view('messages.index', compact('threads', 'selected'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('message_threads') && Schema::hasTable('message_entries'), 503, 'The Message Hub is being activated.');
        abort_unless($request->user()->role === 'student', 403);
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $thread = DB::transaction(function () use ($request, $data) {
            $thread = MessageThread::create([
                'student_id' => $request->user()->id,
                'subject' => trim($data['subject']),
                'last_message_at' => now(),
            ]);
            $thread->messages()->create(['sender_id' => $request->user()->id, 'body' => trim($data['message'])]);

            return $thread;
        });

        record_log('Started Registrar Conversation', 'Messages', "Started message thread #{$thread->id}");

        return redirect()->route('messages.index', ['thread' => $thread])->with('success', 'Your message was sent to the Registrar.');
    }

    public function reply(Request $request, MessageThread $thread): RedirectResponse
    {
        abort_unless(Schema::hasTable('message_threads') && Schema::hasTable('message_entries'), 503, 'The Message Hub is being activated.');
        $this->authorizeThread($thread, $request->user());
        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);

        DB::transaction(function () use ($request, $thread, $data) {
            $thread->messages()->create(['sender_id' => $request->user()->id, 'body' => trim($data['message'])]);
            $thread->update(['last_message_at' => now()]);
        });

        record_log('Replied to Conversation', 'Messages', "Replied to message thread #{$thread->id}");

        return redirect()->route('messages.index', ['thread' => $thread])->with('success', 'Reply sent.');
    }

    private function authorizeThread(MessageThread $thread, $user): void
    {
        abort_unless($user->role === 'registrar' || ($user->role === 'student' && $thread->student_id === $user->id), 403);
    }
}
