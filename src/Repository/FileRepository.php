<?php

declare(strict_types=1);
namespace OnixSystemsPHP\HyperfFileUpload\Repository;

use Carbon\Carbon;
use OnixSystemsPHP\HyperfCore\Model\Builder;
use OnixSystemsPHP\HyperfCore\Repository\AbstractRepository;
use OnixSystemsPHP\HyperfFileUpload\Model\File;

/**
 * @method File create(array $data)
 * @method File update(File $model, array $data)
 * @method File save(File $model)
 * @method bool delete(File $model)
 * @method Builder|FileRepository finder(string $type, ...$parameters)
 * @method null|File fetchOne(bool $lock, bool $force)
 */
class FileRepository extends AbstractRepository
{
    protected string $modelClass = File::class;

    public function getById(int $id, bool $lock = false, bool $force = false): ?File
    {
        return $this->finder('id')->fetchOne($lock, $force);
    }

    public function scopeId(Builder $query, int $id): void
    {
        $query->where('id', '=', $id);
    }

    public function scopeOlderThan(Builder $query, Carbon $time): void
    {
        $query->where('created_at', '<', $time);
    }

    public function scopeUnusedFiles(Builder $query): void
    {
        $query
            ->whereNull('fileable_type')
            ->whereNull('fileable_id')
            ->whereNull('field_name');
    }

    public function scopeDeletedFiles(Builder $query): void
    {
        $query->onlyTrashed();
    }
}
