<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource(
                $this->whenLoaded('user')
            ),
            'date' => $this->work_date?->format('Y-m-d'),
            'clock_in' => $this->clock_in?->format('H:i:s'),
            'clock_out' => $this->clock_out?->format('H:i:s'),
            'total_time' => $this->formattedWorkingTime(),
            'total_break_time' => $this->formattedBreakTime(),
            'comment' => $this->reason,
            'breaks' => AttendanceBreakResource::collection(
                $this->whenLoaded('breaks')
            ),
            'applications' => ApplicationResource::collection(
                $this->whenLoaded('attendanceRequests')
            ),
        ];
    }
}
