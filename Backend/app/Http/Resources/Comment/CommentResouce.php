<?php

namespace App\Http\Resources\Comment;

use App\Http\Resources\JsonResource;

class CommentResouce extends JsonResource
{

    public function toArray($request): array
    {
        return [
            'id'        => $this->id,
            'body'      => $this->body,
            'user'      => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'image' => $this->user->image,
            ],
            'created_at'=> $this->created_at->toDateTimeString(),
        ];
    }
}
