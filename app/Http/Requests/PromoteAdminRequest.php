<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromoteAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('promoteAdmin', $this->route('user'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
