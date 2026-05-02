<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReactivateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reactivate', $this->route('user'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
