<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use stdClass;

class JsonResource extends Resource
{
    private static $data;

    public function toArray($request)
    {
        $merge = [];
        switch (gettype($this::$data)) {
            case 'array':
                $merge = $this::$data;
                if (array_key_exists(0, $merge)) {
                    $merge = [];
                    foreach ($this::$data as $key => $single) {
                        if (is_string($key)) {
                            $merge[$key] = gettype($single) == 'object' ? ($this->$key ? call_user_func($single, $this->$key) : new stdClass) : $single;
                        } else if ($this->resource) {
                            $merge[$single] = method_exists($this->resource, $single) ? $this->$single() : $this->$single;
                        }
                    }
                }
                break;
            case 'NULL':
                $merge = parent::toArray($request);
                break;
            case 'object':
                $merge = call_user_func($this::$data, $this);
                break;
        }
        return $merge;
    }

    public static function noResourceItem($collection, $resource)
    {
        self::$data = $resource;
        $resource = new self($collection);
        return $resource->toArray(request());
    }

    public static function noResourceCollection($collection, $resource, $extra = [])
    {
        self::$data = $resource;
        return self::collection($collection, $extra);
    }

    public static function noResourcePagination($collection, $resource = null, $extra = [])
    {
        self::$data = $resource;
        return self::pagination($collection, $extra);
    }

    public static function item($resource, $extra = [])
    {
        $resource = new static($resource);
        return array_merge($resource->toArray(request()), $extra);
    }

    public static function collection($resource, $extra = [])
    {
        if ($extra)
            return array_merge($extra, ['data' => parent::collection($resource)->toArray(request())]);

        return parent::collection($resource)->toArray(request());
    }

    public static function pagination($resource, $extra = [])
    {
        return array_merge([
            'items' => self::collection($resource->items()),
            'from' => $resource->firstItem(),
            'to' => $resource->lastItem(),
            'per' => $resource->perPage(),
            'total' => $resource->total(),
            'current' => $resource->currentPage(),
            'next_page_url' => $resource->nextPageUrl(),
            'prev_page_url' => $resource->previousPageUrl(),
            'path' => $resource->path(),
        ], $extra);
    }
}
