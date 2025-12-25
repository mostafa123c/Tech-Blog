<?php

namespace App\Http\Resources\Post;

use App\Http\Resources\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'        => $this->id,
            'title'     => $this->title,
            'body'      => $this->body,
            'user'      => $this->LoadUser(),
            'tags'      => $this->LoadTags(),
            'comments_count' => $this->comments_count,
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }

    private function LoadUser()
    {
        return $this->whenLoaded('user', function () {
            return [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'image' => $this->user->image,
            ];
        });
    }

    private function LoadTags()
    {
        return $this->whenLoaded('tags', function () {
            return $this->tags->map(fn($tag) => [
                'id'   => $tag->id,
                'name' => $tag->name,
            ]);
        });
    }
}