<?php

namespace App\Imports;

use App\Models\Post;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Validation\Rule;

class PostsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable;

    protected $errors = [];
    protected $failures = [];
    protected $importedCount = 0;
    protected $skippedCount = 0;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $this->importedCount++;

        return new Post([
            'title' => $row['title'],
            'body' => $row['body'],
            'user_id' => $row['user_id'],
            'image' => $row['image'] ?? null,
        ]);
    }

    /**
     * Define validation rules
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('posts', 'title')
            ],
            'body' => 'required|string',
            'user_id' => 'required|integer|exists:users,id',
            'image' => 'nullable|string',
        ];
    }

    /**
     * Custom error messages
     */
    public function customValidationMessages()
    {
        return [
            'title.required' => 'The title field is required.',
            'title.unique' => 'A post with this title already exists in the database.',
            'body.required' => 'The body field is required.',
            'user_id.required' => 'The user_id field is required.',
            'user_id.exists' => 'The specified user does not exist in the database.',
        ];
    }

    /**
     * Handle errors
     */
    public function onError(\Throwable $e)
    {
        $this->errors[] = $e->getMessage();
        $this->skippedCount++;
    }

    /**
     * Handle validation failures
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
            $this->skippedCount++;
        }
    }

    /**
     * Get errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get validation failures
     */
    public function getFailures()
    {
        return $this->failures;
    }

    /**
     * Get import statistics
     */
    public function getStats()
    {
        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'total_errors' => count($this->errors) + count($this->failures),
        ];
    }
}
