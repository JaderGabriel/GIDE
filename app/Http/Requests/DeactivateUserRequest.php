<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeactivateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('deactivate', $this->route('user'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
