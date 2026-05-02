<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DemoteAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('demoteAdmin', $this->route('user'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
