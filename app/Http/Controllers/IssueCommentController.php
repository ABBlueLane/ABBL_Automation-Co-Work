<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\IssueComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IssueCommentController extends Controller
{
    public function store(Request $request, string $business, Issue $issue): JsonResponse
    {
        if ((string) $issue->business_id !== (string) $business) {
            abort(403);
        }
        if ($issue->status === Issue::STATUS_DRAFT) {
            return response()->json(['message' => 'กรุณาส่งแบบร่างเข้าระบบก่อน จึงจะเพิ่มความคืบหน้าได้'], 422);
        }

        $request->validate([
            'comment' => 'required|string',
            'files' => 'nullable|array',
            'files.*' => 'string',
        ]);

        if ($issue->status === Issue::STATUS_WAITING_REVIEW) {
            $issue->update(['status' => Issue::STATUS_CUSTOMER_REPLIED]);
        }

        $comment = IssueComment::create([
            'issue_id' => $issue->id,
            'user_id' => Auth::id(),
            'comment' => $request->input('comment'),
            'files' => $request->input('files', []),
        ]);

        return response()->json([
            'success' => true,
            'comment' => $comment->load('user'),
        ]);
    }

    public function staffStore(Request $request, string $business, int $id): JsonResponse
    {
        $issue = Issue::where('business_id', $business)->findOrFail($id);

        $request->validate([
            'comment' => 'required|string',
            'files' => 'nullable|array',
            'files.*' => 'string',
            'submit_action' => 'nullable|string|in:save,save_and_review',
        ]);

        $files = $request->input('files', []);
        $submitAction = $request->input('submit_action', 'save');

        $comment = DB::transaction(function () use ($issue, $request, $files, $submitAction) {
            if ($issue->status === Issue::STATUS_PENDING) {
                $issue->update([
                    'assigned_to' => Auth::id(),
                    'status' => Issue::STATUS_IN_PROGRESS,
                ]);
            } elseif ($issue->status === Issue::STATUS_CUSTOMER_REPLIED) {
                $issue->update(['status' => Issue::STATUS_IN_PROGRESS]);
            }

            $newComment = IssueComment::create([
                'issue_id' => $issue->id,
                'user_id' => Auth::id(),
                'comment' => $request->input('comment'),
                'files' => $files,
            ]);

            if (
                $submitAction === 'save_and_review' &&
                in_array($issue->status, [Issue::STATUS_PENDING, Issue::STATUS_IN_PROGRESS, Issue::STATUS_CUSTOMER_REPLIED], true)
            ) {
                $issue->update([
                    'status' => Issue::STATUS_WAITING_REVIEW,
                    'assigned_to' => $issue->assigned_to ?: Auth::id(),
                ]);
            }

            return $newComment;
        });

        $issue->refresh();

        return response()->json([
            'success' => true,
            'status' => $issue->status,
            'comment' => $comment->load('user'),
        ]);
    }
}
