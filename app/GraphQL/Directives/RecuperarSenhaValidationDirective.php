<?php

namespace App\GraphQL\Directives;

use Illuminate\Validation\Rule;
use Nuwave\Lighthouse\Schema\Directives\ValidationDirective;

class RecuperarSenhaValidationDirective extends ValidationDirective
{
    /**
     * @return mixed[]
     */
    public function rules(): array
    {
        return [
            'email' => ['exists:users,email'],
        ];
    }
}
