<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'company' => $this->company,
            'source' => $this->source,
            'status' => $this->status,
            'owner_id' => $this->owner_id,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'assigned_to' => $this->assigned_to,
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'assigned_by' => $this->assigned_by,
            'assigned_at' => optional($this->assigned_at)->toIso8601String(),
            'last_activity_at' => optional($this->last_activity_at)->toIso8601String(),
            'unread_by_owner' => $this->unread_by_owner,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
