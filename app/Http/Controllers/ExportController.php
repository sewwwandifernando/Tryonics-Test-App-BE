<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use App\Models\Export as ExportModel;
use App\Exports\UsersExport;
use App\Exports\PostsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

use App\Imports\UsersImport;
use App\Imports\PostsImport;

class ExportController extends Controller
{
    /**
     * Export users to PDF
     */
    public function exportUsersPdf(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $users = User::with('roles')->get();
        
        // Generate PDF
        $pdf = Pdf::loadView('exports.users-pdf', compact('users'));
        
        // Create filename
        $fileName = 'users_' . date('YmdHis') . '.pdf';
        $filePath = 'exports/pdf/' . $fileName;
        
        // Save PDF to storage
        Storage::disk('public')->put($filePath, $pdf->output());
        
        // Save export record to database
        $export = ExportModel::create([
            'type' => 'users',
            'format' => 'pdf',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'record_count' => $users->count(),
            'user_id' => $user->id,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Users exported to PDF successfully',
            'data' => [
                'export_id' => $export->id,
                'file_name' => $export->file_name,
                'record_count' => $export->record_count,
                'url' => $export->file_url,
                'created_at' => $export->created_at
            ]
        ], 200);
    }

    /**
     * Export users to Excel
     */
    public function exportUsersExcel(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $users = User::all();
        
        // Create filename
        $fileName = 'users_' . date('YmdHis') . '.xlsx';
        $filePath = 'exports/excel/' . $fileName;
        
        // Save Excel to storage
        Excel::store(new UsersExport, $filePath, 'public');
        
        // Save export record to database
        $export = ExportModel::create([
            'type' => 'users',
            'format' => 'excel',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'record_count' => $users->count(),
            'user_id' => $user->id,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Users exported to Excel successfully',
            'data' => [
                'export_id' => $export->id,
                'file_name' => $export->file_name,
                'record_count' => $export->record_count,
                'url' => $export->file_url,
                'created_at' => $export->created_at
            ]
        ], 200);
    }

    /**
     * Export posts to PDF
     */
    public function exportPostsPdf(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $posts = Post::with(['user', 'categories'])->get();
        
        // Generate PDF
        $pdf = Pdf::loadView('exports.posts-pdf', compact('posts'));
        
        // Create filename
        $fileName = 'posts_' . date('YmdHis') . '.pdf';
        $filePath = 'exports/pdf/' . $fileName;
        
        // Save PDF to storage
        Storage::disk('public')->put($filePath, $pdf->output());
        
        // Save export record to database
        $export = ExportModel::create([
            'type' => 'posts',
            'format' => 'pdf',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'record_count' => $posts->count(),
            'user_id' => $user->id,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Posts exported to PDF successfully',
            'data' => [
                'export_id' => $export->id,
                'file_name' => $export->file_name,
                'record_count' => $export->record_count,
                'url' => $export->file_url,
                'created_at' => $export->created_at
            ]
        ], 200);
    }

    /**
     * Export posts to Excel
     */
    public function exportPostsExcel(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $posts = Post::all();
        
        // Create filename
        $fileName = 'posts_' . date('YmdHis') . '.xlsx';
        $filePath = 'exports/excel/' . $fileName;
        
        // Save Excel to storage
        Excel::store(new PostsExport, $filePath, 'public');
        
        // Save export record to database
        $export = ExportModel::create([
            'type' => 'posts',
            'format' => 'excel',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'record_count' => $posts->count(),
            'user_id' => $user->id,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Posts exported to Excel successfully',
            'data' => [
                'export_id' => $export->id,
                'file_name' => $export->file_name,
                'record_count' => $export->record_count,
                'url' => $export->file_url,
                'created_at' => $export->created_at
            ]
        ], 200);
    }

    /**
     * Delete an export file
     */
    public function deleteExport(Request $request, $id)
    {
        $export = ExportModel::find($id);
        
        if (!$export) {
            return response()->json([
                'success' => false,
                'message' => 'Export not found'
            ], 404);
        }
        
        // Delete file from storage
        if (Storage::disk('public')->exists($export->file_path)) {
            Storage::disk('public')->delete($export->file_path);
        }
        
        // Delete record
        $export->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Export deleted successfully'
        ], 200);
    }




    // -------------Excel files Import ---------------------
    /**
     * Import users from Excel file
     */
    public function importUsers(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Validate the uploaded file
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('file');
            
            // Create import instance
            $import = new UsersImport();
            
            // Import the file
            $import->import($file);
            
            // Get statistics
            $stats = $import->getStats();
            $failures = $import->getFailures();
            $errors = $import->getErrors();
            
            // Prepare response
            $response = [
                'success' => true,
                'message' => 'Import completed',
                'data' => [
                    'imported_count' => $stats['imported'],
                    'skipped_count' => $stats['skipped'],
                    'total_errors' => $stats['total_errors'],
                ]
            ];
            
            // Add validation failures if any
            if (!empty($failures)) {
                $response['data']['validation_errors'] = array_map(function($failure) {
                    return [
                        'row' => $failure['row'],
                        'field' => $failure['attribute'],
                        'errors' => $failure['errors'],
                        'data' => $failure['values'],
                    ];
                }, $failures);
            }
            
            // Add general errors if any
            if (!empty($errors)) {
                $response['data']['errors'] = $errors;
            }
            
            // Determine HTTP status code
            $statusCode = 200;
            if ($stats['skipped'] > 0 && $stats['imported'] === 0) {
                $statusCode = 422; // All rows failed
                $response['success'] = false;
                $response['message'] = 'Import failed. All rows have errors.';
            } elseif ($stats['skipped'] > 0) {
                $response['message'] = 'Import completed with some errors';
            }
            
            return response()->json($response, $statusCode);
            
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            
            return response()->json([
                'success' => false,
                'message' => 'Validation errors occurred during import',
                'data' => [
                    'errors' => array_map(function($failure) {
                        return [
                            'row' => $failure->row(),
                            'field' => $failure->attribute(),
                            'errors' => $failure->errors(),
                        ];
                    }, $failures),
                ]
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import posts from Excel file
     */
    public function importPosts(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Validate the uploaded file
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('file');
            
            // Create import instance
            $import = new PostsImport();
            
            // Import the file
            $import->import($file);
            
            // Get statistics
            $stats = $import->getStats();
            $failures = $import->getFailures();
            $errors = $import->getErrors();
            
            // Prepare response
            $response = [
                'success' => true,
                'message' => 'Import completed',
                'data' => [
                    'imported_count' => $stats['imported'],
                    'skipped_count' => $stats['skipped'],
                    'total_errors' => $stats['total_errors'],
                ]
            ];
            
            // Add validation failures if any
            if (!empty($failures)) {
                $response['data']['validation_errors'] = array_map(function($failure) {
                    return [
                        'row' => $failure['row'],
                        'field' => $failure['attribute'],
                        'errors' => $failure['errors'],
                        'data' => $failure['values'],
                    ];
                }, $failures);
            }
            
            // Add general errors if any
            if (!empty($errors)) {
                $response['data']['errors'] = $errors;
            }
            
            // Determine HTTP status code
            $statusCode = 200;
            if ($stats['skipped'] > 0 && $stats['imported'] === 0) {
                $statusCode = 422; // All rows failed
                $response['success'] = false;
                $response['message'] = 'Import failed. All rows have errors.';
            } elseif ($stats['skipped'] > 0) {
                $response['message'] = 'Import completed with some errors';
            }
            
            return response()->json($response, $statusCode);
            
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            
            return response()->json([
                'success' => false,
                'message' => 'Validation errors occurred during import',
                'data' => [
                    'errors' => array_map(function($failure) {
                        return [
                            'row' => $failure->row(),
                            'field' => $failure->attribute(),
                            'errors' => $failure->errors(),
                        ];
                    }, $failures),
                ]
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download sample Excel template for users import
     */
    public function downloadUsersTemplate()
    {
        $headers = [
            ['name', 'email', 'mobile_number', 'address', 'date_of_birth', 'password']
        ];
        
        $sampleData = [
            ['John Doe', 'john@example.com', '0771234567', '123 Main St, Colombo', '1990-01-15', 'password123'],
            ['Jane Smith', 'jane@example.com', '0779876543', '456 Park Ave, Kandy', '1985-05-20', 'password456'],
        ];
        
        return response()->streamDownload(function() use ($headers, $sampleData) {
            $file = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($file, $headers[0]);
            
            // Write sample data
            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        }, 'users_import_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Download sample Excel template for posts import
     */
    public function downloadPostsTemplate()
    {
        $headers = [
            ['title', 'body', 'user_id', 'image']
        ];
        
        $sampleData = [
            ['Sample Post Title 1', 'This is the body content of the first post', '1', ''],
            ['Sample Post Title 2', 'This is the body content of the second post', '1', 'posts/sample.jpg'],
        ];
        
        return response()->streamDownload(function() use ($headers, $sampleData) {
            $file = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($file, $headers[0]);
            
            // Write sample data
            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        }, 'posts_import_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
    }



