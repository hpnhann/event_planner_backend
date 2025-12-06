<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
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
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'max_participants' => 'nullable|integer|min:1',
            'registration_deadline' => 'nullable|date|before:start_date',
            'status' => 'sometimes|in:draft,published,cancelled',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', 
        
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Event title is required',
            'description.required' => 'Event description is required',
            'location.required' => 'Event location is required',
            'start_date.required' => 'Event start date is required',
            'start_date.after' => 'Event must start in the future',
            'end_date.required' => 'Event end date is required',
            'end_date.after' => 'Event must end after start date',
            'max_participants.integer' => 'Max participants must be a number',
            'max_participants.min' => 'Max participants must be at least 1',
            'registration_deadline.before' => 'Registration deadline must be before event start',
            'status.in' => 'Invalid event status',
            'image.image' => 'File must be an image',
            'image.max' => 'Image size cannot exceed 5MB',
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
                'created_by' => auth()->id(),
                'status' => 'draft'
            ]);
        }
        
    }
}