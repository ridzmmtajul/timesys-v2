<?php

namespace App\Rules;

use App\Models\Employee;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueEmployeeName implements ValidationRule
{
    public function __construct(
        private string  $firstName,
        private ?string $middleName,
        private ?string $nameExt,
        private ?string $ignoreId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = Employee::where('first_name', $this->firstName)
            ->where('last_name', $value)
            ->where('name_ext', $this->nameExt);

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        $inputClean   = $this->cleanMiddle($this->middleName);
        $inputInitial = $this->middleInitial($inputClean);

        $conflict = $query->get(['id', 'middle_name'])->contains(function ($emp) use ($inputClean, $inputInitial) {
            $empClean   = $this->cleanMiddle($emp->middle_name);
            $empInitial = $this->middleInitial($empClean);

            // Both have no middle name
            if ($inputClean === null && $empClean === null) {
                return true;
            }

            // One has a middle name and the other doesn't — different people
            if ($inputClean === null || $empClean === null) {
                return false;
            }

            // Both are full names — require exact match
            if (!$this->isInitial($inputClean) && !$this->isInitial($empClean)) {
                return $inputClean === $empClean;
            }

            // At least one side is an initial — compare first letters only
            return $inputInitial === $empInitial;
        });

        if ($conflict) {
            $fail('An employee with this name already exists.');
        }
    }

    // Strips periods/spaces and uppercases; returns null if empty.
    private function cleanMiddle(?string $name): ?string
    {
        $clean = strtoupper(trim(str_replace('.', '', $name ?? '')));
        return $clean !== '' ? $clean : null;
    }

    // Returns the first character of a cleaned middle name, or null if empty.
    private function middleInitial(?string $clean): ?string
    {
        return $clean !== null ? $clean[0] : null;
    }

    // A middle name is an initial when it is exactly one character after cleaning.
    private function isInitial(?string $clean): bool
    {
        return $clean !== null && strlen($clean) === 1;
    }
}
