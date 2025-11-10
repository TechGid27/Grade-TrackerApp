<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    // List all todos for logged-in user
    public function index(Request $request)
    {
        return $request->user()->todos()->with('subject')->get();
    }

    // Store new todo
    public function store(Request $request)
    {
        $request->validate([
            'subject_id' => 'nullable|exists:subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'in:low,medium,high',
            'completed' => 'boolean',
        ]);

        $todo = $request->user()->todos()->create($request->all());

        return response()->json($todo, 201);
    }

    // Show a single todo
    public function show(Request $request, $id)
    {
        $todo = $request->user()->todos()->findOrFail($id);
        return $todo;
    }

    // Update a todo
    public function update(Request $request, $id)
    {
        $todo = $request->user()->todos()->findOrFail($id);

        $request->validate([
            'subject_id' => 'nullable|exists:subjects,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'in:low,medium,high',
            'completed' => 'boolean',
        ]);

        $todo->update($request->all());

        return response()->json($todo);
    }

    // Delete a todo
    public function destroy(Request $request, $id)
    {
        $todo = $request->user()->todos()->findOrFail($id);
        $todo->delete();

        return response()->json(['message' => 'Todo deleted successfully']);
    }
}
