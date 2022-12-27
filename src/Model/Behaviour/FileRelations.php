<?php

declare(strict_types=1);
namespace OnixSystemsPHP\HyperfFileUpload\Model\Behaviour;

use Carbon\Carbon;
use Hyperf\Config\Annotation\Value;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Events\Saved;
use Hyperf\Database\Model\Events\Saving;
use Hyperf\Database\Model\Model;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\HttpMessage\Exception\BadRequestHttpException;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;
use Intervention\Image\ImageManager;
use OnixSystemsPHP\HyperfCore\Model\Behaviour\Parasite;
use OnixSystemsPHP\HyperfFileUpload\Model\File;

/**
 * @property array $fileRelations
 */
trait FileRelations
{
    use Parasite;

    /**
     * <code>
     * [
     *    'field_name' => [
     *        'limit' => 1,
     *        'required' => false,
     *        'mimeTypes' => ['*'],
     *        'presets' => [ // presets array. only for images
     *            '150x150' => [
     *                'fit' => [150, 150],
     *            ],
     *            '250x250' => [
     *                'fit' => [250, 250],
     *            ],
     *        ],
     *    ],
     * ]
     * </code>
     */
    private array $defaultParams = [
        'limit' => 1,
        'required' => false,
        'mimeTypes' => ['*'],
        'presets' => [],
    ];

    #[Value('file_upload.file_actions')]
    private array $extraActions;

    private array $filesCache = [];

    public function boot(): void
    {
        parent::boot();
        if (property_exists($this, 'fileRelations')) {
            foreach ($this->fileRelations as $relationName => $params) {
                $params = $this->fileRelations[$relationName] = $params + $this->defaultParams;
                $morphRelation = $params['limit'] === 1 ? 'morphOne' : 'morphMany';
                self::addExternalMethod($relationName, function () use ($relationName, $morphRelation) {
                    return $this->{$morphRelation}(File::class, 'fileable')->where('field_name', '=', $relationName);
                });
            }
        }
    }

    public function saving(Saving $event)
    {
        foreach ($this->fileRelations ?? [] as $relationName => $params) {
            if (isset($event->getModel()->{$relationName}) && $event->getModel()->isDirty($relationName)) {
                $this->prepareFilesForProcessing($event, $relationName, $params);
                $this->checkLimitedOrRequired($event, $relationName, $params);
            }
        }
    }

    public function saved(Saved $event)
    {
        foreach ($this->fileRelations ?? [] as $relationName => $params) {
            if (!empty($this->filesCache[$relationName])) {
                foreach ($this->filesCache[$relationName] as $file) {
                    $this->processFile($file, $event->getModel(), $relationName);
                    if ($params['limit'] === 1) {
                        $this->deleteOtherActiveFiles($file, $event->getModel(), $relationName);
                    }
                }
            }
        }
        $this->filesCache = [];
    }

    private function prepareFilesForProcessing(Saving $event, string $relationName, array $params): void
    {
        $this->filesCache[$relationName] = [];
        $this->validateUserData($event->getModel()->{$relationName}, $params, $relationName);
        $files = $event->getModel()->{$relationName};
        if ($params['limit'] === 1) {
            $files = [$files];
        }
        foreach ($files as $fileData) {
            $this->filesCache[$relationName][] = $this->getAllowedFileModel($event, $relationName, $fileData);
        }
        $event->getModel()->offsetUnset($relationName);
    }

    private function validateUserData(array $data, array $params, string $relationName): void
    {
        if ($params['limit'] === 1 && empty($data['id'])) {
            throw new BadRequestHttpException(
                "Invalid file data, {$relationName} field should contain one file with id"
            );
        }
        if ($params['limit'] !== 1) {
            foreach ($data as $item) {
                if (!is_array($item) || empty($item['id'])) {
                    throw new BadRequestHttpException(
                        "Invalid file data, {$relationName} field should contain array of files with id"
                    );
                }
            }
        }
    }

    private function checkLimitedOrRequired(Saving $event, string $relationName, array $params): void
    {
        $existingRelations = $event->getModel()->{$relationName}()->get()->all();
        $preparedRelations = $this->filesCache[$relationName];
        $preparedActiveIds = $this->filterActiveFiles($preparedRelations);
        $preparedDeletedIds = $this->filterActiveFiles($preparedRelations, true);
        $storedActiveIds = $this->filterActiveFiles($existingRelations);
        $leftActiveIds = array_filter(array_diff(
            array_unique(array_merge($preparedActiveIds, $storedActiveIds)),
            $preparedDeletedIds
        ));
        if ($params['required'] && count($leftActiveIds) === 0) {
            throw new BadRequestHttpException(
                ucfirst($relationName) . ' is required, at least one file should be assigned'
            );
        }
        if ($params['limit'] > 1 && (count($leftActiveIds) > $params['limit'])) {
            throw new BadRequestHttpException(
                "Only {$params['limit']} files can be assigned to {$relationName}"
            );
        }
    }

    /**
     * @param File[] $files
     * @param bool $revert
     * @return array
     */
    private function filterActiveFiles(array $files, bool $revert = false): array
    {
        return array_filter(array_map(function (File $file) use ($revert) {
            $active = empty($file->deleted_at) && empty($file->delete_it);
            return  ($active === !$revert) ? $file->id : null;
        }, $files));
    }

    private function processFile(File $file, Model $model, string $relationName): void
    {
        if (!$this->isAssigned($file, $model, $relationName)) {
            $this->assignFileToModel($file, $model, $relationName);
            $this->applyModelSettingsToFile($file, $this->fileRelations[$relationName]);
        }
        $this->processFileActions($file);
        $file->save();
    }

    private function deleteOtherActiveFiles(File $file, Model $model, string $relationName): void
    {
        $storedFiles = $model->{$relationName}()->get();
        /** @var File $item */
        foreach ($storedFiles as $item) {
            if ($item->id != $file->id) {
                $item->delete();
            }
        }
    }

    private function isAssigned(File $file, Model $model, string $relationName): bool
    {
        return $file->fileable_type === get_class($model) &&
            $file->fileable_id === $model->getKey() &&
            $file->field_name === $relationName;
    }

    private function assignFileToModel(File $file, Model $model, string $relationName): File
    {
        $file->fileable_type = get_class($model);
        $file->fileable_id = $model->getKey();
        $file->field_name = $relationName;
        return $file;
    }

    private function applyModelSettingsToFile(File $file, array $params): void
    {
        /** @var ConfigInterface $config */
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        if ($file->isImage()) {
            $this->generateImagePresets($file, $params, $config);
        }
    }

    private function generateImagePresets(File $file, array $params, ConfigInterface $config): void
    {
        $storage = $config->get('file.default');
        $filesystem = ApplicationContext::getContainer()->get(FilesystemFactory::class)->get($storage);
        $publicPathPrefix = $config->get("file_upload.storage.{$storage}.public_path_prefix", '');
        $storagePathPrefix = $config->get("file_upload.storage.{$storage}.storage_path_prefix", '');
        $manager = new ImageManager(['driver' => 'gd']);
        foreach ($params['presets'] as $preset => $options) {
            $extension = pathinfo($file->name, PATHINFO_EXTENSION);
            $presetName = "{$preset}.{$extension}";
            $destination = $file->path . DIRECTORY_SEPARATOR . $presetName;
            if ($filesystem->fileExists($destination)) {
                continue;
            }
            $intImage = $manager->make($filesystem->read($file->full_path));
            foreach ($options as $action => $params) {
                if (is_callable($params)) {
                    $intImage = $params($intImage, $filesystem->read($file->full_path));
                } else {
                    $intImage = call_user_func_array([$intImage, $action], $params);
                }
            }
            $filesystem->write($destination, $intImage->encode()->getEncoded(), []);
            $publicDestination = $publicPathPrefix . Str::after($destination, $storagePathPrefix);
            $file->addPreset($preset, "{$file->domain}{$publicDestination}");
        }
    }

    private function processFileActions(File $file): File
    {
        if (!empty($file->offsetGet('delete_it'))) {
            $file->deleted_at = Carbon::now();
        }

        $this->removeActionsFromFile($file);
        return $file;
    }

    private function applyActionsOnFile(File $file, array $fileData)
    {
        foreach ($this->extraActions as $action) {
            if (!empty($fileData[$action])) {
                $file->offsetSet($action, $fileData[$action]);
            }
        }
    }

    private function removeActionsFromFile(File $file)
    {
        foreach ($this->extraActions as $action) {
            $file->offsetUnset($action);
        }
    }

    private function getAllowedFileModel(Saving $event, string $relationName, array $fileData): File
    {
        $fileAvailableForLink = $this->getAllowedToLinkFileModel($event, $relationName, $fileData);

        $fileAllowed = function (Builder $query) use ($event, $relationName, $fileAvailableForLink) {
            $query
                ->where('fileable_type', '=', get_class($event->getModel()))
                ->where('fileable_id', '=', $event->getModel()->getKey())
                ->where('field_name', '=', $relationName)
                ->orWhere($fileAvailableForLink);
        };

        $query = File::where('id', $fileData['id'])->where($fileAllowed);

        $mimeTypes = $this->fileRelations[$relationName]['mimeTypes'] ?? null;
        if (!empty($mimeTypes) && $mimeTypes != ['*']) {
            $query->whereIn('mime', $mimeTypes);
        }

        /** @var ?File $file */
        $file = $query->first();

        if (empty($file)) {
            $allowedFileTypes = $mimeTypes != ['*'] ? implode(', ', array_map(function ($type) {
                [, $type] = explode('/', $type);
                return $type;
            }, $mimeTypes)) : 'any type';
            throw new BadRequestHttpException(
                "File {$fileData['id']} can not to be assigned to {$relationName}. " .
                'Check you have rights to use this file, ' .
                "file not assigned yet and you upload one of following file types: $allowedFileTypes"
            );
        }

        $this->applyActionsOnFile($file, $fileData);

        return $file;
    }

    private function getAllowedToLinkFileModel(Saving $event, string $relationName, array $fileData): callable
    {
        $ownerId = $this->getAuth()?->getKey();

        $fileOwnOrFree = function (Builder $query) use ($ownerId) {
            if (is_null($ownerId)) {
                $query->whereNull('user_id');
            } else {
                $query->where('user_id', '=', $ownerId)->orWhereNull('user_id');
            }
        };

        $fileAvailableForLink = function (Builder $query) use ($fileOwnOrFree) {
            $query
                ->whereNull('fileable_type')
                ->whereNull('fileable_id')
                ->whereNull('field_name')
                ->where($fileOwnOrFree);
        };

        return $fileAvailableForLink;
    }

    private function getAuth(): Model|null
    {
        return null;
    }
}
