<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRules implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validTieBreakers = config('cotas.tie_breakers');
        $encontrado = false;

        foreach ($validTieBreakers as $key) {

            if ($key['key_tie_breakers'] === $value) {
                $encontrado = true;
                break;
            }
        }

        if ($encontrado === false) {
            $fail('O critério de desempate selecionado não é válido.');
        }
    }
}
