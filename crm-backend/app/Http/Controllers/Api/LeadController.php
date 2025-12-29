<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadRequest;
use App\Http\Resources\LeadResource;
use App\Models\BlockedContact;
use App\Models\Lead;
use App\Models\LeadAssignmentLog;
use App\Models\LeadMergeLog;
use App\Models\Notification;
use App\Models\UserDevice;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use App\Services\FcmService;

class LeadController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = Lead::with(['owner','assignee']);

        // Role-based visibility
        if ($user->isAdmin()) {
            // see all
        } elseif ($user->isOwner()) {
            $teamIds = $user->teamMembers()->pluck('id')->toArray();
            $query->where(function($q) use ($user, $teamIds) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('owner_id', $user->id)
                  ->orWhereIn('assigned_to', $teamIds);
            });
        } else {
            $query->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('owner_id', $user->id);
            });
        }

        // Filters
        $query->search($request->q ?? $request->search);
        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($source = $request->source) {
            $query->where('source', $source);
        }
        if ($ownerId = $request->owner_id) {
            $query->where('owner_id', $ownerId);
        }
        if ($assigned = $request->assigned_to) {
            $query->where('assigned_to', $assigned);
        }
        if ($request->boolean('uncontacted')) {
            $query->whereNull('last_activity_at');
        }
        if ($stale = $request->stale_days) {
            $query->where(function($q) use ($stale) {
                $q->whereNull('last_activity_at')
                  ->orWhere('last_activity_at', '<', now()->subDays((int)$stale));
            });
        }

        return LeadResource::collection($query->paginate(10));
    }

    public function store(LeadRequest $request)
    {
        $data = $request->validated();
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // blocked contact check
        if (!empty($data['phone_number']) && BlockedContact::where('phone', $data['phone_number'])->exists()) {
            return response()->json(['message' => 'Phone is blocked'], 409);
        }
        if (!empty($data['email']) && BlockedContact::where('email', $data['email'])->exists()) {
            return response()->json(['message' => 'Email is blocked'], 409);
        }

        // anti-duplicate/tranh khách
        $existing = Lead::where(function($q) use ($data) {
            if (!empty($data['phone_number'])) {
                $q->orWhere('phone_number', $data['phone_number']);
            }
            if (!empty($data['email'])) {
                $q->orWhere('email', $data['email']);
            }
        })->first();

        if ($existing && !($user->isAdmin() || $user->isOwner())) {
            return response()->json([
                'message' => 'Lead already exists',
                'lead_id' => $existing->id
            ], 409);
        }

        $data['owner_id'] = $user->isAdmin() && isset($data['owner_id'])
            ? $data['owner_id']
            : $user->id;
        // Handle assignment: only admin/manager can assign to others
        if (!empty($data['assigned_to']) && !($user->isAdmin() || $user->isOwner())) {
            $data['assigned_to'] = $user->id;
        }
        if (!empty($data['assigned_to'])) {
            $data['assigned_by'] = $user->id;
            $data['assigned_at'] = now();
        }
        $data['unread_by_owner'] = true;
        
        $lead = Lead::create($data);
        return new LeadResource($lead);
    }

    public function show(Lead $lead)
    {
        $this->authorize('view', $lead);
        return new LeadResource($lead->load(['owner','assignee']));
    }

    public function update(LeadRequest $request, Lead $lead)
    {
        $this->authorize('update', $lead);
        $oldStatus = $lead->status;
        $lead->update($request->validated());

        // Notify on status change
        if ($request->filled('status') && $lead->status !== $oldStatus) {
            $targets = collect([$lead->assigned_to, $lead->owner_id])->filter()->unique();
            foreach ($targets as $userId) {
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'LEAD',
                    'content' => "Lead {$lead->full_name} status changed to {$lead->status}",
                    'payload' => ['lead_id' => $lead->id, 'status' => $lead->status],
                ]);

                $tokens = UserDevice::where('user_id', $userId)->pluck('fcm_token')->toArray();
                app(FcmService::class)->send($tokens, 'Lead status updated', "{$lead->full_name}: {$lead->status}", ['lead_id' => $lead->id]);
            }
        }

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
        return $lead->activities()->with('user')->orderBy('created_at', 'desc')->get();
    }

    public function assign(Request $request, Lead $lead)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        $targetId = (int) $request->assigned_to;

        if (!$user->isAdmin()) {
            // Manager can assign to team members or self
            $teamIds = $user->teamMembers()->pluck('id')->toArray();
            if (!in_array($targetId, $teamIds) && $targetId !== $user->id) {
                abort(403, 'Not allowed to assign this lead.');
            }
        }

        $lead->update([
            'assigned_to' => $targetId,
            'assigned_by' => $user->id,
            'assigned_at' => now(),
            'owner_id' => $lead->owner_id ?: $user->id,
        ]);

        LeadAssignmentLog::create([
            'lead_id' => $lead->id,
            'from_user_id' => $lead->assigned_to,
            'to_user_id' => $targetId,
            'assigned_by' => $user->id,
            'note' => $request->input('note'),
        ]);

        Notification::create([
            'user_id' => $targetId,
            'type' => 'LEAD',
            'content' => 'Bạn được giao khách: '.$lead->full_name,
            'payload' => ['lead_id' => $lead->id],
        ]);

        $tokens = UserDevice::where('user_id', $targetId)->pluck('fcm_token')->toArray();
        app(FcmService::class)->send($tokens, 'Lead assigned', $lead->full_name, ['lead_id' => $lead->id]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'lead_assign',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => ['lead_id' => $lead->id, 'to_user' => $targetId],
        ]);

        return new LeadResource($lead->fresh(['owner','assignee']));
    }

    public function merge(Request $request, Lead $lead)
    {
        $request->validate([
            'source_lead_id' => 'required|different:lead|exists:leads,id',
            'note' => 'nullable|string'
        ]);

        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isOwner()) {
            abort(403, 'Only admin/manager can merge leads');
        }

        $source = Lead::findOrFail($request->source_lead_id);

        // move activities/tasks/attachments
        \App\Models\Activity::where('lead_id', $source->id)->update(['lead_id' => $lead->id]);
        \App\Models\Task::where('lead_id', $source->id)->update(['lead_id' => $lead->id]);
        \App\Models\Attachment::where('lead_id', $source->id)->update(['lead_id' => $lead->id]);
        \App\Models\Opportunity::where('lead_id', $source->id)->update(['lead_id' => $lead->id]);

        LeadMergeLog::create([
            'target_lead_id' => $lead->id,
            'source_lead_id' => $source->id,
            'merged_by' => $user->id,
            'note' => $request->note,
        ]);

        $source->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'lead_merge',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => ['target_lead_id' => $lead->id, 'source_lead_id' => $source->id],
        ]);

        return new LeadResource($lead->fresh(['owner','assignee']));
    }
}
