<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExportController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and assigned
| the "api" middleware group. They will all be prefixed with /api.
|
*/
//- - - Public Auth Routes - - -
Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

//- - - Auth/Role Routes - - -

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    
    // Assign role to user
    Route::post('/auth/assign-role', [AuthController::class, 'assignRole'])
        ->name('auth.assignRole')
        ->middleware('role:admin');
        
    
    // Remove role from user
    Route::post('/auth/remove-role', [AuthController::class, 'removeRole'])
        ->name('auth.removeRole')
        ->middleware('role:admin');

    // Get user with roles and permissions
    Route::get('/auth/user/{id}/roles', [AuthController::class, 'getUserWithRoles'])->name('auth.getUserWithRoles');

    // Check if user has permission
    Route::post('/auth/check-permission', [AuthController::class, 'checkPermission'])->name('auth.checkPermission');
});


//- - - Post Routes - - -
Route::middleware('auth:api')->group(function () {

    // returns all posts
    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');

    // adds a post to the database
    Route::post('/posts', [PostController::class, 'store'])
    ->name('posts.store')
         //best way to protect routes with permissions
    ->middleware('permission:create posts');
    

    // returns a single post
    Route::get('/posts/{post}', [PostController::class, 'getPostById'])->name('posts.getPostById');

    // updates a post
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');

    // deletes a post
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    // attach categories to a post (adds without removing existing)
    Route::post('/posts/{post}/categories/attach', [PostController::class, 'attachCategories'])->name('posts.attachCategories');

    // detach categories from a post (removes specified categories)
    Route::post('/posts/{post}/categories/detach', [PostController::class, 'detachCategories'])->name('posts.detachCategories');
});


//- - - User Routes - - -
Route::middleware('auth:api')->group(function () {
    //return all users
    Route::get('/users', [UserController::class, 'index']);

    //create a user
    Route::post('/users', [UserController::class, 'store']);

    //get a user by id
    Route::get('/users/{id}', [UserController::class, 'show']);

    //update a user
    Route::put('/users/{id}', [UserController::class, 'update']);

    //delete a user
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    //get all posts for a specific user
    Route::get('/users/{id}/posts', [UserController::class, 'getUserPosts']);
});

//- - - Category Routes - - -
Route::middleware('auth:api')->group(function () {
    // returns all categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

    // creates a new category
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');

    // returns a single category
    Route::get('/categories/{id}', [CategoryController::class, 'show'])->name('categories.show');

    // updates a category
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');

    // deletes a category
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // get all posts for a specific category
    Route::get('/categories/{id}/posts', [CategoryController::class, 'getCategoryPosts'])->name('categories.getPosts');
});




// Add these routes to your existing api.php file

//- - - Export Routes - - -
Route::middleware('auth:api')->group(function () {
    
    // Export users (POST routes - creates new exports)
    Route::post('/exports/users/pdf', [ExportController::class, 'exportUsersPdf'])
        ->name('exports.users.pdf');
    
    Route::post('/exports/users/excel', [ExportController::class, 'exportUsersExcel'])
        ->name('exports.users.excel');
    
    // Export posts (POST routes - creates new exports)
    Route::post('/exports/posts/pdf', [ExportController::class, 'exportPostsPdf'])
        ->name('exports.posts.pdf');
    
    Route::post('/exports/posts/excel', [ExportController::class, 'exportPostsExcel'])
        ->name('exports.posts.excel');
    
    // Delete export
    Route::delete('/exports/{id}', [ExportController::class, 'deleteExport'])
        ->name('exports.delete')
        ->middleware('role:admin');
});


    //- - - Import Routes - - -
    Route::middleware('auth:api')->group(function () {
        
        // Import users from Excel
        Route::post('/imports/users', [ExportController::class, 'importUsers'])
            ->name('imports.users')
            ->middleware('role:admin');
        
        // Import posts from Excel
        Route::post('/imports/posts', [ExportController::class, 'importPosts'])
            ->name('imports.posts')
            ->middleware('permission:create posts');
        
        // Download import templates
        Route::get('/imports/templates/users', [ExportController::class, 'downloadUsersTemplate'])
            ->name('imports.templates.users');
        
        Route::get('/imports/templates/posts', [ExportController::class, 'downloadPostsTemplate'])
            ->name('imports.templates.posts');
    });