<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachmentRequest;
use App\Models\Lead;
use App\Models\Task;
use App\Models\Attachment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class AttachmentController extends Controller
{
    use AuthorizesRequests;

    public function store(AttachmentRequest $request)
    {
        if ($request->lead_id) {
            $lead = Lead::findOrFail($request->lead_id);
            // Allow any user to upload to any lead
            // $this->authorize('view', $lead);
        }

        if ($request->task_id) {
            $task = Task::findOrFail($request->task_id);
            // Allow any user to upload to any task
            // $this->authorize('view', $task);
        }

        $file = $request->file('file');
        $path = $file->store('attachments', 'public');

        $attachment = Attachment::create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'uploaded_by' => Auth::id(),
            'lead_id' => $request->lead_id,
            'task_id' => $request->task_id
        ]);

        return response()->json($attachment, 201);
    }

    public function destroy(Attachment $attachment)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && $attachment->uploaded_by !== $user->id) {
            abort(403, 'Unauthorized to delete this attachment.');
        }

        // Delete file from storage
        \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);

        $attachment->delete();
        return response()->noContent();
    }
}
