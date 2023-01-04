<?php

declare(strict_types=1);
namespace OnixSystemsPHP\HyperfFileUpload\Resource;

use OnixSystemsPHP\HyperfCore\Resource\AbstractResource;
use OnixSystemsPHP\HyperfFileUpload\Model\File;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ResourceFile',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'url', type: 'string'),
        new OA\Property(property: 'mime', type: 'string'),
        new OA\Property(property: 'presets', type: 'array', items: new OA\Items(type: 'string')),
    ],
    type: 'object',
)]
/**
 * @method __construct(File $resource)
 * @property File $resource
 */
class ResourceFile extends AbstractResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'url' => $this->resource->url,
            'mime' => $this->resource->mime,
            'presets' => $this->resource->presets,
        ];
    }
}
