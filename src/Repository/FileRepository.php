<?php
declare(strict_types=1);

namespace OnixSystemsPHP\HyperfFileUpload\Repository;

use Carbon\Carbon;
use Hyperf\Database\Model\Builder;
use OnixSystemsPHP\HyperfCore\Repository\AbstractRepository;
use OnixSystemsPHP\HyperfFileUpload\Model\File;

/**
 * @method File create(array $data)
 * @method File update(File $model, array $data)
 * @method File save(File $model)
 * @method bool delete(File $model)
 */
class FileRepository extends AbstractRepository
{
    protected string $modelClass = File::class;

    public function getById(int $id, bool $lock = false, bool $force = false): ?File
    {
        return $this->fetchOne($this->queryById($id), $lock, $force);
    }
    public function queryById(int $id): Builder
    {
        return $this->query()->where('id', $id);
    }

    //-----

    public function queryUnusedFilesOlderThen(Carbon $time): Builder
    {
        return $this->queryUnusedFiles()->where('created_at', '<', $time);
    }

    //-----

    public function queryUnusedFiles(): Builder
    {
        return $this->query()
            ->whereNull('fileable_type')
            ->whereNull('fileable_id')
            ->whereNull('field_name');
    }

    //-----

    public function queryDeletedFiles(): Builder
    {
        return File::withTrashed()->whereNotNull('deleted_at');
    }

    //-----

    protected function fetchOne(Builder $builder, bool $lock, bool $force): ?File
    {
        /** @var ?File $result */
        $result = parent::fetchOne($builder, $lock, $force);
        return $result;
    }
}
