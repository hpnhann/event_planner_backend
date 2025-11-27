<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Can check if user owns the event or is admin
        // $event = Event::find($this->route('id'));
        // return $event && ($event->created_by === Auth::id() || Auth::user()->hasRole('admin'));
        
        return true; // For now
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'max_participants' => 'nullable|integer|min:1',
            'status' => 'sometimes|required|in:draft,published,completed,cancelled',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Event title is required',
            'end_date.after' => 'End date must be after start date',
            'max_participants.min' => 'Maximum participants must be at least 1',
            'status.in' => 'Status must be one of: draft, published, completed, cancelled',
        ];
    }
}