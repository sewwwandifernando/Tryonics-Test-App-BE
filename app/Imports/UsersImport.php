<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class UsersImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
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
        // Calculate age from dateOfBirth
        $age = null;
        if (isset($row['date_of_birth'])) {
            try {
                $age = Carbon::parse($row['date_of_birth'])->age;
            } catch (\Exception $e) {
                // Age will be null if date is invalid
            }
        }

        $this->importedCount++;

        return new User([
            'name' => $row['name'],
            'email' => $row['email'],
            'password' => isset($row['password']) ? Hash::make($row['password']) : Hash::make('password123'),
            'mobileNumber' => $row['mobile_number'],
            'address' => $row['address'],
            'dateOfBirth' => $row['date_of_birth'],
            'age' => $age,
        ]);
    }

    /**
     * Define validation rules
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
            ],
            'mobile_number' => [
                'required',
                'string',
                Rule::unique('users', 'mobileNumber')
            ],
            'address' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'password' => 'nullable|string|min:6',
        ];
    }

    /**
     * Custom error messages
     */
    public function customValidationMessages()
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'This email already exists in the database.',
            'mobile_number.required' => 'The mobile number field is required.',
            'mobile_number.unique' => 'This mobile number already exists in the database.',
            'address.required' => 'The address field is required.',
            'date_of_birth.required' => 'The date of birth field is required.',
            'date_of_birth.date' => 'The date of birth must be a valid date.',
            'date_of_birth.before' => 'The date of birth must be before today.',
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