<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ArabicFieldRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // This pattern allows:
        // - Arabic characters: \p{Arabic}
        // - Regular digits: 0-9
        // - Arabic-Indic digits: using hex escapes
        // - Whitespace and line breaks: \p{Z}\r\n
        // - Punctuation: \p{P}
        // - Symbols (including emoji): \p{S}
        // - Combining marks (Arabic diacritics, etc.): \p{M}

        // Arabic-Indic digits (0660-0669) using hex representation
        $arabicDigits = '\x{0660}-\x{0669}';
        $pattern = "/^(?=(.*\p{Arabic}.*)?$)[\p{Arabic}0-9{$arabicDigits}A-Za-z\p{M}\p{P}\p{S}\p{Z}\r\n]+$/u";

        if (! preg_match($pattern, $value)) {
            $fail(trans('validations.admin.arabic_field'));
        }
    }
}
