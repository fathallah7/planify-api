<?php

namespace App\Http\Requests\Task;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', 'string', 'in:todo,in_progress,done'],
            'priority'    => ['nullable', 'string', 'in:low,medium,high'],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
            'due_date'    => ['nullable', 'date', 'after:today'],
        ];
    }
}
