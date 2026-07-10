<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\IssueProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class IssueController extends Controller
{
    public function selectBusiness()
    {
        $businesses = Business::query()
            ->where('business_status', 1)
            ->where('allow_issue', 1)
            ->orderBy('business_name')
            ->get();

        return view('public.issue.business', compact('businesses'));
    }

    public function index(string $business)
    {
        $business = Business::findOrFail($business);

        return view('public.issue.index', compact('business'));
    }

    public function view(string $business, int $id)
    {
        $issue = Issue::where('id', $id)
            ->where('business_id', $business)
            ->with(['firstComment', 'creator', 'assignee'])
            ->firstOrFail();

        if ($issue->status === Issue::STATUS_DRAFT && $issue->created_by !== Auth::id()) {
            abort(403);
        }

        $comments = IssueComment::with('user')
            ->where('issue_id', $issue->id)
            ->skip(1)
            ->latest()
            ->paginate(5);

        return view('public.issue.view', compact('issue', 'comments', 'business'));
    }

    public function table(Request $request, Business $business)
    {
        // 1. เพิ่ม ->withCount('comments') เข้าไปในตอน Query ข้อมูล
        $query = Issue::where('business_id', $business->id)
                      ->withCount('comments'); // Laravel จะสร้างตัวแปร comments_count ให้นับอัตโนมัติ

        // ฟิลเตอร์คำค้นหา
        if ($request->filled('wording')) {
            $wording = trim((string) $request->input('wording'));
            $query->where(function ($subQuery) use ($wording) {
                $subQuery->where('title', 'like', '%'.$wording.'%')
                         ->orWhere('issue_number', 'like', '%'.$wording.'%')
                         ->orWhere('description', 'like', '%'.$wording.'%');
            });
        }

        // ฟิลเตอร์สถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // ฟิลเตอร์ระดับความเร่งด่วน
        if ($request->filled('priority')) {
            $priority = (string) $request->input('priority');
            if (in_array($priority, [Issue::PRIORITY_LOW, Issue::PRIORITY_MEDIUM, Issue::PRIORITY_HIGH], true)) {
                $query->where('priority', $priority);
            }
        }

        // คำนวณจำนวนรายการที่กรองแล้ว (ก่อน pagination)
        $totalFilteredCount = (clone $query)->count();

        $issues = $query->skip((int)$request->input('start', 0))
                        ->take((int)$request->input('length', 100))
                        ->get();

        $data = [];
        foreach ($issues as $issue) {
        $data[] = [
            'id' => $issue->id,
            'issue_number' => $issue->issue_number,
            'title_plain' => $issue->title,
            'description' => $issue->description,
            'status' => $issue->status,
            'priority' => $issue->priority,
            'view_url' => route('issue.view', [$business, $issue->id]), // หรือ Route ปลายทางของคุณ
            'created_at_formatted' => $issue->created_at->format('d กรกฎาคม Y'), // ตัวอย่างฟอร์แมต
            
            // 2. [จุดสำคัญ] ต้องส่งค่า comments_count กลับมาให้หน้าบ้านด้วย
            'comments_count' => (int)$issue->comments_count,
        ];
    }

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => Issue::where('business_id', $business->id)->count(),
            'recordsFiltered' => $totalFilteredCount, // จำนวนที่กรองแล้ว
            'data' => $data,
        ]);
}

    public function create(Request $request, string $business)
    {
        $issue = null;
        $isDuplicateTemplate = false;

        if ($request->filled('duplicate')) {
            $issue = Issue::with(['firstComment', 'issueProject'])->findOrFail((int) $request->query('duplicate'));
            $isDuplicateTemplate = true;
        } elseif ($request->filled('draft')) {
            $issue = Issue::with(['firstComment', 'issueProject'])
                ->where('business_id', $business)
                ->where('created_by', Auth::id())
                ->where('status', Issue::STATUS_DRAFT)
                ->findOrFail((int) $request->query('draft'));
        }

        $issueProjects = IssueProject::query()->orderBy('name')->get();

        if ($issueProjects->isEmpty()) {
            $businesses = Business::query()
                ->where('business_status', 1)
                ->where('allow_issue', 1)
                ->orderBy('business_name')
                ->get();

            $responsibleUserId = Auth::id();

            foreach ($businesses as $biz) {
                IssueProject::create([
                    'name' => $biz->business_name,
                    'responsible_user_id' => $responsibleUserId,
                ]);
            }

            $issueProjects = IssueProject::query()->orderBy('name')->get();
        }

        $isIssueEmployee = false;

        return view('public.issue.create', compact('business', 'issue', 'issueProjects', 'isIssueEmployee', 'isDuplicateTemplate'));
    }

    public function saveDraft(Request $request, string $business): JsonResponse
    {
        Business::findOrFail($business);

        $request->validate([
            'issue_id' => ['nullable', 'integer', 'exists:issues,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
            'url' => ['nullable', 'string', 'max:2048'],
            'files' => ['nullable', 'array'],
            'files.*' => ['string'],
            'issue_project_id' => ['nullable', 'integer', 'exists:issue_projects,id'],
            'priority' => ['nullable', 'in:'.implode(',', array_keys(Issue::getPriorityOptions()))],
        ]);

        $priority = $this->resolvePriorityFromRequest($request);
        $issueId = $request->input('issue_id');
        $userId = Auth::id();

        $issue = DB::transaction(function () use ($request, $business, $issueId, $userId, $priority) {
            $title = trim((string) $request->input('title', ''));
            if ($title === '') {
                $title = 'แบบร่าง';
            }

            if ($issueId) {
                $draft = Issue::where('id', $issueId)
                    ->where('business_id', $business)
                    ->where('created_by', $userId)
                    ->where('status', Issue::STATUS_DRAFT)
                    ->firstOrFail();

                $draft->update([
                    'title' => $title,
                    'url' => $request->input('url') ?: null,
                    'issue_project_id' => $request->filled('issue_project_id') ? (int) $request->input('issue_project_id') : null,
                    'priority' => $priority,
                ]);

                $comment = $draft->firstComment;
                if ($comment) {
                    $comment->update([
                        'comment' => $request->input('comment'),
                        'files' => $request->input('files', []),
                    ]);
                } else {
                    IssueComment::create([
                        'issue_id' => $draft->id,
                        'user_id' => $userId,
                        'comment' => $request->input('comment'),
                        'files' => $request->input('files', []),
                    ]);
                }

                return $draft;
            }

            $draft = Issue::create([
                'business_id' => $business,
                'issue_project_id' => $request->filled('issue_project_id') ? (int) $request->input('issue_project_id') : null,
                'issue_number' => Issue::generateDraftIssueNumber(),
                'title' => $title,
                'url' => $request->input('url') ?: null,
                'status' => Issue::STATUS_DRAFT,
                'priority' => $priority,
                'created_by' => $userId,
                'assigned_to' => null,
            ]);

            IssueComment::create([
                'issue_id' => $draft->id,
                'user_id' => $userId,
                'comment' => $request->input('comment'),
                'files' => $request->input('files', []),
            ]);

            return $draft;
        });

        return response()->json([
            'success' => true,
            'issue_id' => $issue->id,
            'redirect_view' => route('issue.view', [$business, $issue->id]),
        ]);
    }

    public function storeAndSubmit(Request $request, string $business): JsonResponse
    {
        Business::findOrFail($business);
        $this->mergeSubmitUrlEmptyToNull($request);
        $request->validate($this->issueSubmitRules());

        $issueProject = $request->filled('issue_project_id')
            ? IssueProject::find((int) $request->input('issue_project_id'))
            : null;

        $issue = DB::transaction(function () use ($request, $business, $issueProject) {
            $newIssue = Issue::create([
                'business_id' => $business,
                'issue_project_id' => $issueProject?->id,
                'issue_number' => Issue::getNo($business),
                'title' => $request->input('title'),
                'url' => $request->input('url'),
                'status' => Issue::STATUS_PENDING,
                'priority' => $request->input('priority'),
                'created_by' => Auth::id(),
                'assigned_to' => $issueProject?->responsible_user_id,
            ]);

            IssueComment::create([
                'issue_id' => $newIssue->id,
                'user_id' => Auth::id(),
                'comment' => $request->input('comment'),
                'files' => $request->input('files', []),
            ]);

            return $newIssue;
        });

        $issue->load(['firstComment', 'creator', 'assignee', 'issueProject']);

        return response()->json([
            'success' => true,
            'redirect' => route('issue.view', [$business, $issue->id]),
            'issue_number' => $issue->issue_number,
            'issue_id' => $issue->id,
            'html' => view('public.issue.view-content', [
                'issue' => $issue,
                'comments' => collect([]),
                'business' => $business,
                'isPreview' => false,
            ])->render(),
        ]);
    }

    public function submitDraft(Request $request, string $business, Issue $issue): JsonResponse
    {
        if ((string) $issue->business_id !== (string) $business) {
            abort(404);
        }
        if ($issue->created_by !== Auth::id()) {
            abort(403);
        }
        if ($issue->status !== Issue::STATUS_DRAFT) {
            return response()->json(['message' => 'รายการนี้ไม่ใช่แบบร่าง'], 422);
        }

        if ($request->boolean('from_stored')) {
            $issue->loadMissing('firstComment');
            $request->merge([
                'title' => $issue->title,
                'comment' => $issue->firstComment?->comment,
                'url' => $issue->url,
                'files' => $issue->firstComment?->files ?? [],
                'issue_project_id' => $issue->issue_project_id,
                'priority' => $issue->priority,
            ]);
        }

        $this->mergeSubmitUrlEmptyToNull($request);
        $request->validate($this->issueSubmitRules());

        $issueProject = $request->filled('issue_project_id')
            ? IssueProject::find((int) $request->input('issue_project_id'))
            : null;

        DB::transaction(function () use ($issue, $business, $request, $issueProject) {
            $issue->update([
                'issue_project_id' => $issueProject?->id,
                'issue_number' => Issue::getNo($business),
                'title' => $request->input('title'),
                'url' => $request->input('url'),
                'status' => Issue::STATUS_PENDING,
                'priority' => $request->input('priority'),
                'assigned_to' => $issueProject?->responsible_user_id,
            ]);

            $first = $issue->fresh()->firstComment;
            if ($first) {
                $first->update([
                    'comment' => $request->input('comment'),
                    'files' => $request->input('files', []),
                ]);
            } else {
                IssueComment::create([
                    'issue_id' => $issue->id,
                    'user_id' => Auth::id(),
                    'comment' => $request->input('comment'),
                    'files' => $request->input('files', []),
                ]);
            }
        });

        $issue = $issue->fresh()->load(['firstComment', 'creator', 'assignee', 'issueProject']);

        return response()->json([
            'success' => true,
            'redirect' => route('issue.view', [$business, $issue->id]),
            'issue_number' => $issue->issue_number,
            'issue_id' => $issue->id,
            'html' => view('public.issue.view-content', [
                'issue' => $issue,
                'comments' => collect([]),
                'business' => $business,
                'isPreview' => false,
            ])->render(),
        ]);
    }

    public function upload(Request $request, string $business): JsonResponse
    {
        Business::findOrFail($business);

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,mp4,mov,webm,pdf,doc,docx,xls,xlsx,csv,txt,md,html,htm,json,xml,css,js|max:51200',
        ]);

        $file = $request->file('file');
        $path = $file->store("issue/{$business}", 'public');

        return response()->json([
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'type' => $file->getClientMimeType(),
        ]);
    }

    public function staffUpload(Request $request): JsonResponse
    {
        $businessId = officeBusinessId();
        if (! $businessId) {
            abort(403);
        }

        return $this->upload($request, $businessId);
    }

    public function close(string $business, Issue $issue): JsonResponse
    {
        if ((string) $issue->business_id !== (string) $business || $issue->created_by !== Auth::id()) {
            abort(403);
        }

        if ($issue->status === Issue::STATUS_DRAFT) {
            return response()->json(['message' => 'กรุณาส่งแบบร่างเข้าระบบก่อน'], 422);
        }

        $issue->update([
            'status' => Issue::STATUS_DONE,
            'complete_at' => now(),
        ]);

        IssueComment::create([
            'issue_id' => $issue->id,
            'user_id' => Auth::id(),
            'comment' => (Auth::user()?->full_name ?? 'ระบบ').' ปิดงานนี้เรียบร้อยแล้ว (ผู้แจ้ง)',
            'files' => [],
        ]);

        return response()->json(['success' => true]);
    }

    public function duplicate(string $business, Issue $issue)
    {
        if ((string) $issue->business_id !== (string) $business) {
            abort(403);
        }

        return redirect(route('issue.create', [$business, 'duplicate' => $issue->id]));
    }

    public function preview(Request $request, string $business)
    {
        Business::findOrFail($business);
        $this->mergeSubmitUrlEmptyToNull($request);

        $request->validate([
            'title' => 'required|string|max:255',
            'priority' => ['required', 'in:'.implode(',', array_keys(Issue::getPriorityOptions()))],
            'comment' => 'required|string',
            'url' => 'nullable|url|max:2048',
            'files' => 'nullable|array',
            'files.*' => 'string',
            'issue_project_id' => ['nullable', 'integer', 'exists:issue_projects,id'],
        ]);

        $issue = new Issue;
        $issue->title = $request->input('title');
        $issue->url = $request->input('url');
        $issue->status = Issue::STATUS_PENDING;
        $issue->issue_number = 'PREVIEW';
        $issue->created_at = now();
        $issue->priority = $request->input('priority');
        $issue->setRelation('creator', Auth::user());
        $issue->setRelation('assignee', null);

        $comment = new IssueComment;
        $comment->comment = $request->input('comment');
        $comment->files = (array) $request->input('files', []);
        $issue->setRelation('firstComment', $comment);

        $issueProject = $request->filled('issue_project_id')
            ? IssueProject::find((int) $request->input('issue_project_id'))
            : null;
        $issue->setRelation('issueProject', $issueProject);

        $comments = collect([]);

        return view('public.issue._form_review_summary', compact('issue', 'issueProject', 'business'))->render();
    }

    public function adminIndex()
    {
        $staffs = User::query()->where('status', 'active')->orderBy('first_name')->get();
        $issueProjects = IssueProject::query()->orderBy('name')->get();
        $businesses = Business::query()
            ->where('business_status', 1)
            ->orderBy('business_name')
            ->get();

        return view('issue.index', compact('staffs', 'issueProjects', 'businesses'));
    }

    public function adminTable(Request $request): JsonResponse
    {
        $query = Issue::with(['business', 'assignee', 'issueProject'])
            ->where('status', '!=', Issue::STATUS_DRAFT)
            ->addSelect([
                'last_comment_created_at' => IssueComment::query()
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn('issue_id', 'issues.id'),
            ]);

        if ($request->filled('business_id')) {
            $query->where('business_id', $request->input('business_id'));
        }

        $this->applyIssueFilters($query, $request);

        $recordsTotal = (clone $query)->count();
        $rows = $query->skip((int) $request->input('start', 0))
            ->take((int) $request->input('length', 100))
            ->get();

        $data = [];
        foreach ($rows as $i => $issue) {
            $viewUrl = route('office.issue.view', ['business' => $issue->business_id, 'id' => $issue->id]);
            $data[] = [
                'DT_RowIndex' => (int) $request->input('start', 0) + $i + 1,
                'issue_number' => '<a href="'.$viewUrl.'" class="text-primary">'.e($issue->issue_number).'</a>',
                'business_html' => $issue->business?->business_name ? e($issue->business->business_name) : '<span class="text-muted">-</span>',
                'issue_project_html' => $issue->issueProject?->name ? e($issue->issueProject->name) : '<span class="text-muted">-</span>',
                'title_html' => '<a href="'.$viewUrl.'" class="text-primary">'.e($issue->title).'</a>',
                'status_html' => $this->renderIssueStatusBadge($issue->status),
                'priority_html' => (string) $issue->getPriorityBadgeHtml(),
                'planned_start_at_html' => $this->formatDateTimeForTable($issue->planned_start_at),
                'due_at_html' => $this->formatDateTimeForTable($issue->due_at),
                'schedule_status_html' => $this->renderScheduleStatusBadge($issue),
                'created_elapsed_html' => $this->formatIssueCreatedElapsedHtml($issue),
                'last_action_at_html' => $this->formatIssueLastActionAtHtml($issue),
                'assigned_to_html' => '<div class="d-flex align-items-center justify-content-between gap-2"><span>'.e($issue->assignee?->full_name ?? '-').'</span><button type="button" class="btn btn-sm btn-outline-primary" onclick="openAssignModal('.e((string) $issue->id).', '.e((string) $issue->business_id).')"><i class="ri-user-add-line"></i> Assign</button></div>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
        ]);
    }

    public function staffIndex(Request $request, string $business)
    {
        Session::put('mainBusinessID', $business);
        $staffs = User::query()->where('status', 'active')->orderBy('first_name')->get();
        $issueProjects = IssueProject::query()->orderBy('name')->get();

        return view('office.issue.index', compact('staffs', 'issueProjects'));
    }

    public function staffTable(Request $request, string $business): JsonResponse
    {
        Session::put('mainBusinessID', $business);

        $query = Issue::with(['business', 'assignee', 'issueProject'])
            ->where('business_id', $business)
            ->where('status', '!=', Issue::STATUS_DRAFT)
            ->addSelect([
                'last_comment_created_at' => IssueComment::query()
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn('issue_id', 'issues.id'),
            ]);

        $this->applyIssueFilters($query, $request);

        $recordsTotal = (clone $query)->count();
        $rows = $query->skip((int) $request->input('start', 0))
            ->take((int) $request->input('length', 100))
            ->get();

        $data = [];
        foreach ($rows as $i => $issue) {
            $data[] = [
                'DT_RowIndex' => (int) $request->input('start', 0) + $i + 1,
                'issue_number' => '<a href="'.officeBusinessRoute('issue.view', ['business' => $business, 'id' => $issue->id]).'" class="text-primary">'.e($issue->issue_number).'</a>',
                'issue_project_html' => $issue->issueProject?->name ? e($issue->issueProject->name) : '<span class="text-muted">-</span>',
                'title_html' => '<a href="'.officeBusinessRoute('issue.view', ['business' => $business, 'id' => $issue->id]).'" class="text-primary">'.e($issue->title).'</a>',
                'status_html' => $this->renderIssueStatusBadge($issue->status),
                'priority_html' => (string) $issue->getPriorityBadgeHtml(),
                'planned_start_at_html' => $this->formatDateTimeForTable($issue->planned_start_at),
                'due_at_html' => $this->formatDateTimeForTable($issue->due_at),
                'schedule_status_html' => $this->renderScheduleStatusBadge($issue),
                'created_elapsed_html' => $this->formatIssueCreatedElapsedHtml($issue),
                'last_action_at_html' => $this->formatIssueLastActionAtHtml($issue),
                'assigned_to_html' => '<div class="d-flex align-items-center justify-content-between gap-2"><span>'.e($issue->assignee?->full_name ?? '-').'</span><button type="button" class="btn btn-sm btn-outline-primary" onclick="openAssignModal('.e((string) $issue->id).')"><i class="ri-user-add-line"></i> Assign</button></div>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
        ]);
    }

    public function staffAssignModal(string $business, int $id)
    {
        Session::put('mainBusinessID', $business);
        $issue = Issue::where('business_id', $business)->findOrFail($id);
        if ($issue->status === Issue::STATUS_DRAFT) {
            abort(404);
        }
        $staffs = User::query()->where('status', 'active')->orderBy('first_name')->get();

        return view('office.issue.ajax.modalAssign', compact('issue', 'staffs'));
    }

    public function staffView(Request $request, string $business, int $id)
    {
        Session::put('mainBusinessID', $business);

        $issue = Issue::with(['business', 'firstComment', 'creator', 'assignee'])
            ->where('business_id', $business)
            ->findOrFail($id);
        if ($issue->status === Issue::STATUS_DRAFT) {
            abort(404);
        }

        $comments = IssueComment::with('user')
            ->where('issue_id', $issue->id)
            ->skip(1)
            ->latest()
            ->paginate(5);

        return view('office.issue.view', compact('issue', 'comments'));
    }

    public function staffClose(string $business, int $id): JsonResponse
    {
        Session::put('mainBusinessID', $business);
        $issue = Issue::where('business_id', $business)->findOrFail($id);
        if ($issue->status === Issue::STATUS_DRAFT) {
            abort(404);
        }

        $issue->update([
            'status' => Issue::STATUS_DONE,
            'complete_at' => now(),
        ]);

        IssueComment::create([
            'issue_id' => $issue->id,
            'user_id' => Auth::id(),
            'comment' => (Auth::user()?->full_name ?? 'ระบบ').' ปิดงานนี้เรียบร้อยแล้ว (Staff)',
            'files' => [],
        ]);

        return response()->json(['success' => true]);
    }

    public function staffReview(string $business, int $id): JsonResponse
    {
        Session::put('mainBusinessID', $business);
        $issue = Issue::where('business_id', $business)->findOrFail($id);
        if ($issue->status === Issue::STATUS_DRAFT) {
            abort(404);
        }
        $issue->update(['status' => Issue::STATUS_WAITING_REVIEW]);

        return response()->json(['success' => true]);
    }

    public function staffAssign(Request $request, string $business, int $id): JsonResponse
    {
        Session::put('mainBusinessID', $business);
        $issue = Issue::where('business_id', $business)->findOrFail($id);
        if ($issue->status === Issue::STATUS_DRAFT) {
            abort(404);
        }

        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'planned_start_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:planned_start_at'],
        ]);

        $plannedStart = $request->filled('planned_start_at') ? Carbon::parse($request->input('planned_start_at')) : null;
        $dueAt = $request->filled('due_at') ? Carbon::parse($request->input('due_at')) : null;
        $assignee = User::findOrFail((int) $request->input('user_id'));

        $issue->update([
            'assigned_to' => (int) $request->input('user_id'),
            'assigned_by' => Auth::id(),
            'assigned_at' => now(),
            'planned_start_at' => $plannedStart,
            'due_at' => $dueAt,
        ]);

        IssueComment::create([
            'issue_id' => $issue->id,
            'user_id' => Auth::id(),
            'comment' => sprintf(
                '%s มอบหมายให้ %s | เริ่ม: %s | กำหนดเสร็จ: %s',
                Auth::user()?->full_name ?? 'ระบบ',
                $assignee->full_name,
                $plannedStart?->format('d/m/Y H:i') ?? '-',
                $dueAt?->format('d/m/Y H:i') ?? '-'
            ),
            'files' => [],
        ]);

        if ($issue->status === Issue::STATUS_PENDING) {
            $issue->update(['status' => Issue::STATUS_IN_PROGRESS]);
        }

        return response()->json(['success' => true]);
    }

    public function staffPriority(Request $request, string $business, int $id): JsonResponse
    {
        Session::put('mainBusinessID', $business);
        $issue = Issue::where('business_id', $business)->findOrFail($id);
        if ($issue->status === Issue::STATUS_DRAFT) {
            abort(404);
        }

        $request->validate([
            'priority' => 'required|in:'.implode(',', array_keys(Issue::getPriorityOptions())),
        ]);
        $issue->update(['priority' => $request->input('priority')]);

        return response()->json(['success' => true, 'label' => $issue->priority_label]);
    }

    protected function applyIssueFilters(Builder $query, Request $request): void
    {
        if ($request->filled('wording')) {
            $wording = trim((string) $request->input('wording'));
            $query->where(function (Builder $sub) use ($wording) {
                $sub->where('title', 'like', '%'.$wording.'%')
                    ->orWhere('issue_number', 'like', '%'.$wording.'%');
            });
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'open') {
                $query->where('status', '!=', Issue::STATUS_DONE);
            } else {
                $query->where('status', $request->input('status'));
            }
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }
        if ($request->filled('issue_project_id')) {
            $query->where('issue_project_id', $request->input('issue_project_id'));
        }
        if ($request->has('assigned_to')) {
            $value = $request->input('assigned_to');
            if ($value === 'null') {
                $query->whereNull('assigned_to');
            } elseif ($value !== '' && $value !== null) {
                $query->where('assigned_to', $value);
            }
        }

        $query->latest();
    }

    protected function renderIssueStatusBadge(?string $status): string
    {
        $meta = Issue::getStatusMeta($status);

        return '<span class="badge '.e($meta['class']).'">'.e($meta['label']).'</span>';
    }

    protected function formatDateTimeForTable($value): string
    {
        if (! $value) {
            return '-';
        }

        return e(Carbon::parse($value)->format('d/m/Y H:i'));
    }

    protected function formatIssueCreatedElapsedHtml(Issue $issue): string
    {
        if (! $issue->created_at) {
            return '-';
        }

        $created = Carbon::parse($issue->created_at);
        $days = $created->copy()->startOfDay()->diffInDays(now()->startOfDay());

        return '<div class="text-nowrap">'.e($created->format('d/m/Y H:i')).'</div><small class="text-muted">เปิดมา '.e((string) $days).' วัน</small>';
    }

    protected function formatIssueLastActionAtHtml(Issue $issue): string
    {
        $fromComment = $issue->getAttribute('last_comment_created_at');
        $candidates = collect([$issue->updated_at, $fromComment ? Carbon::parse($fromComment) : null])->filter();
        $latest = $candidates->sortDesc()->first();
        if (! $latest instanceof Carbon) {
            return '-';
        }

        return '<span class="text-nowrap">'.e($latest->format('d/m/Y H:i')).'</span>';
    }

    protected function renderScheduleStatusBadge(Issue $issue): string
    {
        if (! $issue->planned_start_at || ! $issue->due_at) {
            return '<span class="badge bg-secondary">ยังไม่กำหนดแผนเวลา</span>';
        }
        if ($issue->status !== Issue::STATUS_DONE && now()->gt($issue->due_at)) {
            return '<span class="badge bg-danger">ค้างเกินกำหนด</span>';
        }

        return '<span class="badge bg-success">ตามแผน</span>';
    }

    protected function mergeSubmitUrlEmptyToNull(Request $request): void
    {
        if ($request->input('url') === '') {
            $request->merge(['url' => null]);
        }
    }

    protected function issueSubmitRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'priority' => ['required', 'in:'.implode(',', array_keys(Issue::getPriorityOptions()))],
            'comment' => 'required|string',
            'url' => 'nullable|url|max:2048',
            'files' => 'nullable|array',
            'files.*' => 'string',
            'issue_project_id' => ['nullable', 'integer', 'exists:issue_projects,id'],
        ];
    }

    protected function resolvePriorityFromRequest(Request $request): string
    {
        $valid = array_keys(Issue::getPriorityOptions());
        $priority = $request->input('priority');

        if (is_string($priority) && in_array($priority, $valid, true)) {
            return $priority;
        }

        return Issue::PRIORITY_MEDIUM;
    }
}
