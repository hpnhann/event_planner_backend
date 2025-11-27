<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For now return true, later can check if user has permission
        // Example: return $this->user()->hasRole('admin') || $this->user()->hasRole('organizer');
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'max_participants' => 'nullable|integer|min:1',
            'status' => 'nullable|in:draft,published,completed,cancelled',
            'created_by' => 'required|exists:users,id'
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Event title is required',
            'title.max' => 'Event title cannot exceed 255 characters',
            'start_date.required' => 'Start date is required',
            'start_date.after' => 'Start date must be in the future',
            'end_date.required' => 'End date is required',
            'end_date.after' => 'End date must be after start date',
            'max_participants.min' => 'Maximum participants must be at least 1',
            'status.in' => 'Status must be one of: draft, published, completed, cancelled',
            'created_by.required' => 'Creator user ID is required',
            'created_by.exists' => 'The specified user does not exist',
        ];
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'start_date' => 'start date',
            'end_date' => 'end date',
            'max_participants' => 'maximum participants',
            'created_by' => 'creator',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-set status to 'draft' if not provided
        if (!$this->has('status')) {
            $this->merge([
                'status' => 'draft'
            ]);
        }

        // If using Auth, can auto-set created_by
        // if (Auth::check() && !$this->has('created_by')) {
        //     $this->merge([
        //         'created_by' => Auth::id()
        //     ]);
        // }
    }
}