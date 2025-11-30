<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInterventionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'scheduled_at' => 'sometimes|date',
            'ticket_id' => 'nullable|exists:tickets,id',
            'user_id' => 'sometimes|exists:users,id',
            'status' => 'sometimes|in:planned,in_progress,completed,cancelled',
        ];
    }
}
