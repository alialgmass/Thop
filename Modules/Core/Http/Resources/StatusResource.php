<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Support\ModelState\BaseState;

/**
 * @mixin BaseState
 */
class StatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'value' => $this->getValue(),
            'label' => $this->label(),
            'transitionable_states' => $this->transitionableStates(),
            'color' => [
                'value' => $this->color()->value,
                'class' => $this->color()->styleClass(),
                'hexadecimal' => $this->color()->hexadecimal(),
            ],
            'comment' => (string) $this->getModel()->lastLog?->comment,
        ];
    }
}
