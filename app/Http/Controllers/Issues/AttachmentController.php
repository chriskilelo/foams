<?php

namespace App\Http\Controllers\Issues;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Issue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class AttachmentController extends Controller
{
    /**
     * Upload a file and attach it to an issue.
     * Files are stored as UUID filenames under storage/app/private.
     */
    public function store(Request $request, Issue $issue): RedirectResponse
    {
        $this->authorize('view', $issue);

        $request->validate([
            'file' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'pdf', 'mp4', 'log'])
                    ->max(10 * 1024),
            ],
        ]);

        $uploadedFile = $request->file('file');

        $storedName = Str::uuid().'.'.$uploadedFile->getClientOriginalExtension();

        $uploadedFile->storeAs('attachments', $storedName, 'private');

        Attachment::create([
            'attachable_type' => Issue::class,
            'attachable_id' => $issue->id,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $uploadedFile->getMimeType(),
            'size_bytes' => $uploadedFile->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'File attached.');
    }

    /**
     * Generate a 15-minute signed temporary URL to download an attachment.
     */
    public function show(Attachment $attachment): JsonResponse
    {
        $this->authorize('view', $attachment->attachable);

        $url = Storage::disk('private')->temporaryUrl(
            'attachments/'.$attachment->stored_name,
            now()->addMinutes(15)
        );

        return response()->json(['url' => $url]);
    }

    /**
     * Delete an attachment (removes the physical file and the record).
     */
    public function destroy(Attachment $attachment): RedirectResponse
    {
        $issue = $attachment->attachable;

        $this->authorize('view', $issue);

        Storage::disk('private')->delete('attachments/'.$attachment->stored_name);

        $attachment->delete();

        return back()->with('success', 'Attachment removed.');
    }
}
