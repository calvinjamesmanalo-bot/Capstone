<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function store(Request $request)
    {
        Announcement::create($request->validateWithBag('createAnnouncement', [
            'body' => ['required', 'string', 'max:2000'],
        ]));

        return redirect()->route('settings.index')->with('success', 'Announcement created successfully.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $announcement->update($request->validateWithBag('announcement'.$announcement->id, [
            'body' => ['required', 'string', 'max:2000'],
        ]));

        return redirect()->route('settings.index')->with('success', 'Announcement updated successfully.');
    }

    public function archive(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('settings.index')->with('success', 'Announcement archived successfully.');
    }
}
