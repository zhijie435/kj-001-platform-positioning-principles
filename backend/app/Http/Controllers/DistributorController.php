<?php

namespace App\Http\Controllers;

use App\Http\Requests\DistributorRequest;
use App\Http\Resources\DistributorResource;
use App\Models\Distributor;
use Illuminate\Http\Request;

class DistributorController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:distributor.view')->only(['index', 'show']);
        $this->middleware('permission:distributor.create')->only(['store']);
        $this->middleware('permission:distributor.edit')->only(['update']);
        $this->middleware('permission:distributor.delete')->only(['destroy']);
        $this->middleware('permission:distributor.approve')->only(['approve']);
    }

    public function index(Request $request)
    {
        $query = Distributor::visibleTo($request->user())
            ->with(['parent:id,name'])
            ->withCount('users');

        $this->applySearch($query, $request, ['name', 'company_name', 'contact_person', 'phone', 'region']);

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        }

        return DistributorResource::collection(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(DistributorRequest $request)
    {
        $distributor = Distributor::create($request->validated());

        return new DistributorResource($distributor);
    }

    public function show(Request $request, Distributor $distributor)
    {
        Distributor::visibleTo($request->user())->where('id', $distributor->id)->firstOrFail();

        return new DistributorResource($distributor->load(['parent', 'children'])->loadCount('users'));
    }

    public function update(DistributorRequest $request, Distributor $distributor)
    {
        Distributor::visibleTo($request->user())->where('id', $distributor->id)->firstOrFail();

        $data = $request->validated();

        if (isset($data['parent_id']) && (int) $data['parent_id'] === $distributor->id) {
            unset($data['parent_id']);
        }

        $distributor->update($data);

        return new DistributorResource($distributor);
    }

    public function destroy(Request $request, Distributor $distributor)
    {
        Distributor::visibleTo($request->user())->where('id', $distributor->id)->firstOrFail();

        $distributor->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function approve(Request $request, Distributor $distributor)
    {
        Distributor::visibleTo($request->user())->where('id', $distributor->id)->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', 'in:active,rejected,suspended'],
            'remark' => ['nullable', 'string'],
        ]);

        $distributor->status = $validated['status'];
        $distributor->remark = $validated['remark'] ?? $distributor->remark;
        $distributor->save();

        return new DistributorResource($distributor);
    }

    public function toggleStatus(Request $request, Distributor $distributor)
    {
        Distributor::visibleTo($request->user())->where('id', $distributor->id)->firstOrFail();

        $distributor->status = $distributor->status === 'active' ? 'suspended' : 'active';
        $distributor->save();

        return new DistributorResource($distributor);
    }
}
