<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\User;
use Carbon\Carbon;

class CustomRuleAge implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = User::find($value);

        if (!$user) {
            $fail('The selected user does not exist.');
            return;
        }

        $age = Carbon::parse($user->dateOfBirth)->age;

        if ($age < 18) {
            $fail('The user must be at least 18 years old to create a post.');
        }
    }
}
