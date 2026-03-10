<?php

namespace App\Http\Controllers\Issues;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\IssueActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IssueActivityController extends Controller
{
    /**
     * Append a comment or field note to an issue's activity log.
     * IssueActivity records are append-only — no update or delete endpoints exist.
     */
    public function store(Request $request, Issue $issue): RedirectResponse
    {
        $this->authorize('view', $issue);

        $validated = $request->validate([
            'action_type' => ['required', 'in:comment,field_note'],
            'comment' => ['required', 'string', 'max:2000'],
            'is_internal' => ['boolean'],
        ]);

        IssueActivity::create([
            'issue_id' => $issue->id,
            'user_id' => $request->user()->id,
            'action_type' => $validated['action_type'],
            'previous_status' => null,
            'new_status' => null,
            'comment' => $validated['comment'],
            'is_internal' => $validated['is_internal'] ?? false,
        ]);

        return back()->with('success', 'Comment added.');
    }
}
