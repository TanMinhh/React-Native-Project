<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OpportunityRequest;
use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
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
        $data['probability'] = $data['probability'] ?? $this->defaultProbability($data['stage'] ?? null);
        if (!isset($data['expected_revenue']) && isset($data['estimated_value'])) {
            $data['expected_revenue'] = $data['estimated_value'] * ($data['probability'] / 100);
        }
        $data['stage_updated_at'] = now();

        $opportunity = Opportunity::create($data);
        $this->logStageHistory($opportunity, null, $opportunity->stage, $opportunity->probability);

        return new OpportunityResource($opportunity->load(['owner', 'lead']));
    }

    public function show(Opportunity $opportunity)
    {
        $this->authorize('view', $opportunity);
        return new OpportunityResource($opportunity->load(['owner', 'lead', 'lineItems', 'stageHistories.changer']));
    }

    public function update(OpportunityRequest $request, Opportunity $opportunity)
    {
        $this->authorize('update',$opportunity);
        $data = $request->validated();
        $oldStage = $opportunity->stage;

        if (isset($data['stage']) && $data['stage'] !== $oldStage) {
            $data['stage_updated_at'] = now();
            if (!isset($data['probability'])) {
                $data['probability'] = $this->defaultProbability($data['stage']);
            }
        }

        if (!isset($data['expected_revenue'])) {
            $prob = $data['probability'] ?? $opportunity->probability ?? 0;
            $baseValue = $data['estimated_value'] ?? $opportunity->estimated_value ?? 0;
            $data['expected_revenue'] = $baseValue * ($prob / 100);
        }

        $opportunity->update($data);

        if (isset($data['stage']) && $data['stage'] !== $oldStage) {
            $this->logStageHistory($opportunity, $oldStage, $data['stage'], $data['probability'] ?? $opportunity->probability);
        }

        return new OpportunityResource($opportunity->load(['owner', 'lead', 'lineItems', 'stageHistories.changer']));
    }

    public function destroy(Opportunity $opportunity)
    {
        $this->authorize('delete', $opportunity);
        $opportunity->delete();
        return response()->noContent();
    }

    public function pipeline()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = Opportunity::query();
        if ($user->isStaff()) {
            abort(403, 'Staff cannot access opportunities.');
        }
        if (!$user->isAdmin()) {
            $query->where('owner_id', $user->id);
        }

        $summary = $query->selectRaw('stage, COUNT(*) as total, SUM(expected_revenue) as expected_revenue')
            ->groupBy('stage')
            ->get();

        return response()->json(['data' => $summary]);
    }

    public function forecast()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->isStaff()) {
            abort(403, 'Staff cannot access opportunities.');
        }

        $query = Opportunity::query();
        if (!$user->isAdmin()) {
            $query->where('owner_id', $user->id);
        }

        $forecast = $query->whereNotNull('expected_close_date')
            ->selectRaw("DATE_FORMAT(expected_close_date, '%Y-%m') as month, SUM(expected_revenue) as expected_revenue, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        return response()->json(['data' => $forecast]);
    }

    private function defaultProbability(?string $stage): int
    {
        return match ($stage) {
            'PROSPECTING' => 10,
            'PROPOSAL' => 30,
            'NEGOTIATION' => 60,
            'WON' => 100,
            'LOST' => 0,
            default => 50,
        };
    }

    private function logStageHistory(Opportunity $opportunity, ?string $fromStage, string $toStage, ?int $probability): void
    {
        OpportunityStageHistory::create([
            'opportunity_id' => $opportunity->id,
            'changed_by' => Auth::id(),
            'from_stage' => $fromStage,
            'to_stage' => $toStage,
            'probability' => $probability,
            'changed_at' => now(),
        ]);
    }
}
