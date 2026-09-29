<section aria-labelledby="announcement-management-heading" class="space-y-5 border-b border-slate-200 pb-5">

    <form method="POST" action="{{ route('announcements.store') }}" class="space-y-3">
        @csrf
        <label for="new-announcement" class="block text-sm font-semibold text-slate-800">Create Announcement</label>
        <textarea id="new-announcement" name="body" required maxlength="2000" rows="3" class="block w-full rounded-lg border-2 border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#000638] focus:ring-1 focus:ring-[#000638]">{{ $errors->createAnnouncement->any() ? old('body') : '' }}</textarea>
        @error('body', 'createAnnouncement')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-[#000638] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#10175c] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#000638] focus-visible:ring-offset-2">Create Announcement</button>
        </div>
    </form>

    <div class="space-y-3">
        <h4 class="text-sm font-semibold text-slate-800">Active Announcements</h4>
        @forelse($announcements as $announcement)
            <article class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="whitespace-pre-line break-words text-sm text-slate-700">{{ $announcement->body }}</p>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <time datetime="{{ $announcement->created_at->toIso8601String() }}" class="text-xs text-slate-500">{{ $announcement->created_at->format('M j, Y') }}</time>
                    <div class="flex gap-2">
                        <button type="button" data-announcement-edit="announcement-{{ $announcement->id }}" aria-haspopup="dialog" aria-controls="announcement-{{ $announcement->id }}" class="rounded-lg border border-[#000638]/20 bg-[#000638]/[0.05] px-3 py-2 text-xs font-semibold text-[#000638] transition hover:bg-[#000638]/10">Edit</button>
                        <form method="POST" action="{{ route('announcements.archive', $announcement) }}" onsubmit="return confirm('Archive this announcement? It will no longer appear to students.');">
                            @csrf @method('PATCH')
                            <button type="submit" class="rounded-lg border border-slate-300 bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">Archive</button>
                        </form>
                    </div>
                </div>
            </article>
            @php($editBag = 'announcement'.$announcement->id)
            <dialog id="announcement-{{ $announcement->id }}" aria-labelledby="announcement-title-{{ $announcement->id }}" class="announcement-dialog m-auto w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-black/40" @if($errors->getBag($editBag)->any()) data-reopen @endif>
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 p-5">
                    <h2 id="announcement-title-{{ $announcement->id }}" class="text-xl font-semibold">Edit Announcement</h2>
                    <button type="button" data-announcement-close aria-label="Close edit announcement" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-[#000638]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('announcements.update', $announcement) }}" class="space-y-4 p-5">
                    @csrf @method('PATCH')
                    <label for="announcement-body-{{ $announcement->id }}" class="block text-sm font-semibold text-slate-800">Announcement</label>
                    <textarea id="announcement-body-{{ $announcement->id }}" name="body" required maxlength="2000" rows="5" autofocus class="block w-full rounded-lg border-2 border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#000638] focus:ring-1 focus:ring-[#000638]">{{ $errors->getBag($editBag)->any() ? old('body') : $announcement->body }}</textarea>
                    @error('body', $editBag)<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    <div class="flex justify-end">
                        <button type="submit" class="rounded-lg bg-[#000638] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#10175c]">Save Changes</button>
                    </div>
                </form>
            </dialog>
        @empty
            <p class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">No active announcements.</p>
        @endforelse
    </div>
</section>
<script>
    document.querySelectorAll('[data-announcement-edit]').forEach(button => {
        button.addEventListener('click', () => document.getElementById(button.dataset.announcementEdit).showModal());
    });
    document.querySelectorAll('.announcement-dialog').forEach(dialog => {
        dialog.querySelector('[data-announcement-close]').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => {
            if (event.target !== dialog) return;
            const bounds = dialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
        });
        if (dialog.hasAttribute('data-reopen')) dialog.showModal();
    });
</script>
