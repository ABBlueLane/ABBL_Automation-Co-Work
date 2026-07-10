<?php

namespace App\Http\Controllers;

use App\Models\IssueProject;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IssueProjectController extends Controller
{
    public function index(string $business)
    {
        $staffs = $this->staffs();

        return view('office.issue.project', compact('staffs'));
    }

    public function table(Request $request, string $business): JsonResponse
    {
        $query = IssueProject::query()->with('responsibleUser')->orderBy('name');
        $recordsTotal = (clone $query)->count();
        $rows = $query->skip((int) $request->input('start', 0))
            ->take((int) $request->input('length', 50))
            ->get();

        $data = [];
        foreach ($rows as $i => $row) {
            $data[] = [
                'DT_RowIndex' => (int) $request->input('start', 0) + $i + 1,
                'name' => e($row->name),
                'responsible_name' => e($row->responsibleUser?->full_name ?? '-'),
                'actions' => '<div class="d-flex gap-1 justify-content-center"><button type="button" class="btn btn-warning btn-sm" onclick="openEditModal('.(int) $row->id.')"><i class="ri-pencil-line"></i></button><button type="button" class="btn btn-danger btn-sm" onclick="deleteProject('.(int) $row->id.')"><i class="ri-delete-bin-line"></i></button></div>',
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
        $staffs = $this->staffs();

        return view('office.issue.ajax.modalProjectAdd', compact('staffs'));
    }

    public function modalEdit(string $business, int $id)
    {
        $project = IssueProject::findOrFail($id);
        $staffs = $this->staffs();

        return view('office.issue.ajax.modalProjectEdit', compact('project', 'staffs'));
    }

    public function store(Request $request, string $business): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'responsible_user_id' => 'required|integer|exists:users,id',
        ]);

        IssueProject::create($data);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, string $business, int $id): JsonResponse
    {
        $project = IssueProject::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'responsible_user_id' => 'required|integer|exists:users,id',
        ]);
        $project->update($data);

        return response()->json(['success' => true]);
    }

    public function destroy(string $business, int $id): JsonResponse
    {
        $project = IssueProject::findOrFail($id);
        $project->delete();

        return response()->json(['success' => true]);
    }

    protected function staffs()
    {
        return User::query()->where('status', 'active')->orderBy('first_name')->get();
    }
}
