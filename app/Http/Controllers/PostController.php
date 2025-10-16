<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\User;
use App\Rules\CustomRuleAge;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Check if user has permission
        $userId = $request->header('X-User-Id') ?? $request->query('user_id');
        
        if ($userId) {
            $user = User::find($userId);
            if ($user && !$user->hasPermissionTo('view posts')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You do not have permission to view posts.'
                ], 403);
            }
        }

        // Eager load the user and categories relationships
        $posts = Post::with(['user:id,name', 'categories'])->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Posts retrieved successfully',
            'data' => $posts
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Get authenticated user
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // if (!$user->hasPermissionTo('create posts')) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthorized. You do not have permission to create posts.'
        //     ], 403);
        // }

        // Validate the age of the authenticated user
        $ageValidator = new CustomRuleAge();
        $ageValidator->validate('user_id', $user->id, function($message) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                response()->json([
                    'success' => false,
                    'message' => $message
                ], 422)
            );
        });

        $request->validate([
            'title' => [
                'required',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = Post::where('title', $request->title)->exists();
                    if ($exists) {
                        $fail('A post with the same title already exists.');
                    }
                },
            ],
            'body' => 'required',
            //'user_id' => ['required', 'exists:users,id', new CustomRuleAge()],
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:3072',
        ]);

        $insertData = [
            'title' => $request->title,
            'body' => $request->body,
            'user_id' => $user->id, 
        ];
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->store('posts', 'public');
            $insertData['image'] = $imagePath;
        }
      
        $post = Post::create($insertData);
        
        // Attach categories if provided
        if ($request->has('category_ids')) {
            $post->categories()->attach($request->category_ids);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => $post->load(['user', 'categories'])
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $post = Post::find($id);
        
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        $user = User::find($request->user_id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check permissions
        $isOwner = $post->user_id == $request->user_id;
        $canEditOwn = $user->hasPermissionTo('edit own posts');
        $canEditAll = $user->hasPermissionTo('edit posts');

        if (!$canEditAll && !($isOwner && $canEditOwn)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have permission to edit this post.'
            ], 403);
        }

        $request->validate([
            'title' => 'required|max:255',
            'body' => 'required',
            'user_id' => ['required', 'exists:users,id', new CustomRuleAge()],
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:3072',
        ]);

        $updateData = [
            'title' => $request->title,
            'body' => $request->body,
            'user_id' => $request->user_id,
        ];
        
        // Handle image upload and delete old image
        if ($request->hasFile('image')) {
            $post->deleteImage();
            $image = $request->file('image');
            $imagePath = $image->store('posts', 'public');
            $updateData['image'] = $imagePath;
        }
        
        $post->update($updateData);
        
        // Sync categories
        if ($request->has('category_ids')) {
            $post->categories()->sync($request->category_ids);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'data' => $post->load(['user', 'categories'])
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $post = Post::find($id);
        
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        // Get user_id from request
        $userId = $request->header('X-User-Id') ?? $request->query('user_id');
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID required'
            ], 401);
        }

        $user = User::find($userId);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check permissions
        $isOwner = $post->user_id == $userId;
        $canDeleteOwn = $user->hasPermissionTo('delete own posts');
        $canDeleteAll = $user->hasPermissionTo('delete posts');

        if (!$canDeleteAll && !($isOwner && $canDeleteOwn)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have permission to delete this post.'
            ], 403);
        }
        
        $post->deleteImage();
        $post->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully',
        ], 200);
    }

    /**
     * Get a post by ID with categories.
     */
    public function getPostById(Request $request, $id) 
    {
        $userId = $request->header('X-User-Id') ?? $request->query('user_id');
        
        if ($userId) {
            $user = User::find($userId);
            if ($user && !$user->hasPermissionTo('view posts')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You do not have permission to view posts.'
                ], 403);
            }
        }

        $post = Post::with(['user', 'categories'])->find($id);
        
        if ($post) {
            return response()->json([
                'success' => true,
                'data' => $post
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }
    }

    /**
     * Delete the image from a post.
     */
    public function deleteImage(Request $request, $id)
    {
        $post = Post::find($id);
        
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        $userId = $request->header('X-User-Id') ?? $request->query('user_id');
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID required'
            ], 401);
        }

        $user = User::find($userId);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check permissions (same as edit)
        $isOwner = $post->user_id == $userId;
        $canEditOwn = $user->hasPermissionTo('edit own posts');
        $canEditAll = $user->hasPermissionTo('edit posts');

        if (!$canEditAll && !($isOwner && $canEditOwn)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have permission to edit this post.'
            ], 403);
        }
        
        if (!$post->image) {
            return response()->json([
                'success' => false,
                'message' => 'Post has no image'
            ], 400);
        }
        
        $post->deleteImage();
        $post->update(['image' => null]);
        
        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully',
            'data' => $post
        ], 200);
    }

    /**
     * Attach categories to a post.
     */
    public function attachCategories(Request $request, $id)
    {
        $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $post = Post::find($id);
        
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        $user = User::find($request->user_id);
        $isOwner = $post->user_id == $request->user_id;
        $canEditOwn = $user->hasPermissionTo('edit own posts');
        $canEditAll = $user->hasPermissionTo('edit posts');

        if (!$canEditAll && !($isOwner && $canEditOwn)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have permission to edit this post.'
            ], 403);
        }
        
        $post->categories()->syncWithoutDetaching($request->category_ids);
        
        return response()->json([
            'success' => true,
            'message' => 'Categories attached successfully',
            'data' => $post->load('categories')
        ], 200);
    }

    /**
     * Detach categories from a post.
     */
    public function detachCategories(Request $request, $id)
    {
        $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $post = Post::find($id);
        
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        $user = User::find($request->user_id);
        $isOwner = $post->user_id == $request->user_id;
        $canEditOwn = $user->hasPermissionTo('edit own posts');
        $canEditAll = $user->hasPermissionTo('edit posts');

        if (!$canEditAll && !($isOwner && $canEditOwn)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have permission to edit this post.'
            ], 403);
        }
        
        $post->categories()->detach($request->category_ids);
        
        return response()->json([
            'success' => true,
            'message' => 'Categories detached successfully',
            'data' => $post->load('categories')
        ], 200);
    }

    // Routes functions for views
    public function create()
    {
        return view('posts.create');
    }

    public function show($id)
    {
        $post = Post::with(['user', 'categories'])->find($id);
        return view('posts.show', compact('post'));
    }

    public function edit($id)
    {
        $post = Post::with(['user', 'categories'])->find($id);
        return view('posts.edit', compact('post'));
    }
}