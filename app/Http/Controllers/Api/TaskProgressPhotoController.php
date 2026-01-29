<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskProgressPhoto;
use Illuminate\Http\Request;

class TaskProgressPhotoController extends Controller
{
    /**
     * Get all photos for a task
     */
    public function index(Task $task)
    {
        $photos = $task->progressPhotos()
            ->with('employee:id,name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $photos,
        ]);
    }

    /**
     * Get photos grouped by date for a task
     */
    public function byDate(Task $task)
    {
        $photos = $task->progressPhotos()
            ->with('employee:id,name')
            ->get()
            ->groupBy(function ($photo) {
                return $photo->captured_date->format('Y-m-d');
            });

        return response()->json([
            'success' => true,
            'data' => $photos,
        ]);
    }

    /**
     * Store a new progress photo
     */
    public function store(Request $request, Task $task)
    {
        $validated = $request->validate([
            'photo' => 'required|string', // Base64 encoded image
            'caption' => 'nullable|string|max:500',
            'captured_date' => 'nullable|date',
        ]);

        // Get the current authenticated user's employee_id
        $user = $request->user();

        $photo = TaskProgressPhoto::create([
            'task_id' => $task->id,
            'employee_id' => $user->employee_id ?? $user->id,
            'photo' => $validated['photo'],
            'caption' => $validated['caption'] ?? null,
            'captured_date' => $validated['captured_date'] ?? now()->toDateString(),
        ]);

        $photo->load('employee:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Progress photo uploaded successfully',
            'data' => $photo,
        ], 201);
    }

    /**
     * Delete a progress photo
     */
    public function destroy(TaskProgressPhoto $photo)
    {
        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Progress photo deleted',
        ]);
    }
}
