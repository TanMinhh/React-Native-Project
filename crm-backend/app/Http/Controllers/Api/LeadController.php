<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadRequest;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = Lead::with('owner')
            ->when(!$user->isAdmin(), function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            })
            ->search($request->search);

        return LeadResource::collection($query->paginate(10));
    }

    public function store(LeadRequest $request)
    {
        $data = $request->validated();
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $data['owner_id'] = $user->isAdmin() && isset($data['owner_id'])
            ? $data['owner_id']
            : $user->id;
        $data['unread_by_owner'] = true;
        
        $lead = Lead::create($data);
        return new LeadResource($lead);
    }

    public function show(Lead $lead)
    {
        $this->authorize('view', $lead);
        return new LeadResource($lead->load('owner'));
    }

    public function update(LeadRequest $request, Lead $lead)
    {
        $this->authorize('update', $lead);
        $lead->update($request->validated());
        return new LeadResource($lead);
    }

    public function destroy(Lead $lead)
    {
        $this->authorize('delete', $lead);
        $lead->delete();
        return response()->noContent();
    }

    public function activities(Lead $lead)
    {
        $this->authorize('view', $lead);
        return $lead->activities;
    }
}
