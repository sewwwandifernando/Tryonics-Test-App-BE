<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully',
            'data' => $users
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:100',
            'mobileNumber' => 'required|unique:users,mobileNumber',
            'address' => 'required|max:100',
            'dateOfBirth' => ['required', 'date'],
        ]);
        $insertData = $request->all();
        User::create($insertData);
        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully',
            'data' => $user
        ], 200);
    }

    /**
     * Get all posts for a specific user.
     */
    public function getUserPosts(string $id)
    {
        $user = User::with('posts')->find($id);
        
        if (is_null($user)) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'User posts retrieved successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'posts' => $user->posts
            ]
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|max:100',
            'mobileNumber' => 'required|unique:users,mobileNumber,'.$id,
            'address' => 'required|max:100',
            'dateOfBirth' => 'required|date',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ], 200);
    }
}