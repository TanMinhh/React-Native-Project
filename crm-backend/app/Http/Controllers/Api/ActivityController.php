<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Lead;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:CALL,EMAIL,MESSAGE,MEETING,NOTE',
            'content' => 'required|string',
            'lead_id' => 'required|exists:leads,id',
        ]);

        $lead = Lead::findOrFail($data['lead_id']);
        $this->authorize('view', $lead);

        return Activity::create([
            'type'=>$data['type'],
            'content'=>$data['content'],
            'lead_id'=>$data['lead_id'],
            'user_id'=>Auth::id()
        ]);
    }
}
