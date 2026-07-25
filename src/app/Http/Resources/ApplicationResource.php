<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
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
            'status' => $this->status,
            'requested_clock_in' => $this->requested_clock_in?->format('H:i:s'),
            'requested_clock_out' => $this->requested_clock_out?->format('H:i:s'),
            'reason' => $this->reason,
        ];
    }
}
