<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function profileRules(User|int|null $userOrId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userOrId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function emailRules(User|int|null $userOrId = null): array
    {
        $uniqueRule = Rule::unique(User::class);

        if ($userOrId !== null) {
            $uniqueRule = $userOrId instanceof User
                ? $uniqueRule->ignore($userOrId)
                : $uniqueRule->ignore($userOrId);
        }

        return [
            'required',
            'string',
            'email',
            'max:255',
            $uniqueRule,
        ];
    }
}
