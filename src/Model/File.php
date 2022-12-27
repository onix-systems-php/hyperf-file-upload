<?php

declare(strict_types=1);
namespace OnixSystemsPHP\HyperfFileUpload\Model;

use Carbon\Carbon;
use Hyperf\Config\Annotation\Value;
use Hyperf\Database\Model\SoftDeletes;
use OnixSystemsPHP\HyperfCore\Model\AbstractOwnedModel;

/**
 * @property int $id
 * @property int $user_id
 * @property string $fileable_id
 * @property string $fileable_type
 * @property string $field_name
 * @property string $storage
 * @property string $path
 * @property string $name
 * @property string $full_path
 * @property string $domain
 * @property string $url
 * @property string $original_name
 * @property int $size
 * @property string $mime
 * @property array $presets
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class File extends AbstractOwnedModel
{
    use SoftDeletes;

    #[Value('file_upload.image_mime_types')]
    protected array $imageMimeTypes;

    protected $table = 'files';

    protected $guarded = [];

    protected $hidden = [];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'fileable_id' => 'string',
        'fileable_type' => 'string',
        'field_name' => 'string',
        'storage ' => 'string',
        'path' => 'string',
        'name' => 'string',
        'full_path' => 'string',
        'domain' => 'string',
        'url' => 'string',
        'original_name' => 'string',
        'size' => 'integer',
        'mime' => 'string',
        'presets' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function fileable()
    {
        return $this->morphTo();
    }

    public function isImage(): bool
    {
        return in_array($this->mime, $this->imageMimeTypes);
    }

    public function addPreset(string $name, string $url): self
    {
        $presets = $this->presets;
        if (empty($presets)) {
            $presets = [];
        }
        $presets[$name] = $url;
        $this->presets = $presets;
        return $this;
    }
}
