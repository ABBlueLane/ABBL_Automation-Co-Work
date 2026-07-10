<?php

namespace App\Http\Controllers;

use App\Models\CriticalIssue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CriticalIssueController extends Controller
{
    public function index(string $business)
    {
        return view('office.issue.critical');
    }

    public function table(Request $request, string $business): JsonResponse
    {
        $query = CriticalIssue::query()->with('createdByUser')->latest();
        $recordsTotal = (clone $query)->count();
        $rows = $query->skip((int) $request->input('start', 0))
            ->take((int) $request->input('length', 50))
            ->get();

        $data = [];
        foreach ($rows as $i => $row) {
            $data[] = [
                'DT_RowIndex' => (int) $request->input('start', 0) + $i + 1,
                'problem' => e(Str::limit($row->problem, 80)),
                'solution' => e(Str::limit($row->solution, 80)),
                'tools' => e(Str::limit($row->tools, 60)),
                'created_by_name' => e($row->createdByUser?->full_name ?? '-'),
                'created_at' => optional($row->created_at)?->format('d/m/Y H:i') ?: '-',
                'actions' => '<div class="d-flex gap-1 justify-content-center"><button type="button" class="btn btn-warning btn-sm" onclick="openEditModal('.(int) $row->id.')"><i class="ri-pencil-line"></i></button><button type="button" class="btn btn-danger btn-sm" onclick="deleteCriticalIssue('.(int) $row->id.')"><i class="ri-delete-bin-line"></i></button></div>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
        ]);
    }

    public function modalAdd(string $business)
    {
        return view('office.issue.ajax.modalCriticalAdd');
    }

    public function modalEdit(string $business, int $id)
    {
        $criticalIssue = CriticalIssue::with('createdByUser')->findOrFail($id);

        return view('office.issue.ajax.modalCriticalEdit', compact('criticalIssue'));
    }

    public function store(Request $request, string $business): JsonResponse
    {
        $data = $request->validate([
            'problem' => 'required|string',
            'solution' => 'required|string',
            'tools' => 'required|string',
        ]);

        CriticalIssue::create([
            ...$data,
            'created_by' => Auth::id(),
        ]);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, string $business, int $id): JsonResponse
    {
        $criticalIssue = CriticalIssue::findOrFail($id);
        $data = $request->validate([
            'problem' => 'required|string',
            'solution' => 'required|string',
            'tools' => 'required|string',
        ]);
        $criticalIssue->update($data);

        return response()->json(['success' => true]);
    }

    public function destroy(string $business, int $id): JsonResponse
    {
        $criticalIssue = CriticalIssue::findOrFail($id);
        $criticalIssue->delete();

        return response()->json(['success' => true]);
    }
}
