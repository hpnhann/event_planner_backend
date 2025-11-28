<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            // Format ngày tháng: Năm-Tháng-Ngày (VD: 2025-11-28)
            'start_date' => $this->start_date, 
            'time' => $this->start_time,
            'location' => $this->location,
            'capacity' => $this->max_attendees,
            'status' => $this->status,
            // Trả về thông tin người tạo (nếu có load user)
            'organizer' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
        ];
    }
}