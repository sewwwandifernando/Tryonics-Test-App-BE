<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Post;

class CheckPostOwnership
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $postId = $request->route('post') ?? $request->route('id');
        
        if (!$postId) {
            return response()->json([
                'success' => false,
                'message' => 'Post ID not provided'
            ], 400);
        }

        $post = Post::find($postId);
        
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        // Get the authenticated user from request
        $userId = $request->user_id ?? $request->input('user_id');
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $user = \App\Models\User::find($userId);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check if user is admin or post owner
        if ($user->hasRole('admin') || $post->user_id == $userId) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. You can only modify your own posts.'
        ], 403);
    }
}
