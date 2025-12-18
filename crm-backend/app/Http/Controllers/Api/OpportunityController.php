<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OpportunityRequest;
use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class OpportunityController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isStaff()) {
            abort(403, 'Staff cannot access opportunities.');
        }

        $query = Opportunity::with(['owner', 'lead'])
            ->when(!$user->isAdmin(), function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            })
            ->search($request->search);

        return OpportunityResource::collection($query->paginate(10));
    }

    public function store(OpportunityRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();

        /** @var \App\Models\User $user */
        if ($user->isStaff()) {
            abort(403, 'Staff cannot create opportunities.');
        }

        $data['owner_id'] = $user->isAdmin() && isset($data['owner_id'])
            ? $data['owner_id']
            : $user->id;
        
        $opportunity = Opportunity::create($data);
        return new OpportunityResource($opportunity);
    }

    public function show(Opportunity $opportunity)
    {
        $this->authorize('view', $opportunity);
        return new OpportunityResource($opportunity->load(['owner', 'lead']));
    }

    public function update(OpportunityRequest $request, Opportunity $opportunity)
    {
        $this->authorize('update',$opportunity);
        $opportunity->update($request->validated());
        return new OpportunityResource($opportunity);
    }

    public function destroy(Opportunity $opportunity)
    {
        $this->authorize('delete', $opportunity);
        $opportunity->delete();
        return response()->noContent();
    }
}
