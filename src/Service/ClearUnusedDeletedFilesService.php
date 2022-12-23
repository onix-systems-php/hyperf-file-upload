<?php
declare(strict_types=1);

namespace OnixSystemsPHP\HyperfFileUpload\Service;

use Carbon\Carbon;
use Hyperf\Contract\ConfigInterface;
use Hyperf\DbConnection\Annotation\Transactional;
use Hyperf\Filesystem\FilesystemFactory;
use League\Flysystem\FilesystemException;
use OnixSystemsPHP\HyperfActionsLog\Event\Action;
use OnixSystemsPHP\HyperfCore\Service\Service;
use OnixSystemsPHP\HyperfFileUpload\Model\File;
use OnixSystemsPHP\HyperfFileUpload\Repository\FileRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

#[Service]
class ClearUnusedDeletedFilesService
{
    public const ACTION = 'deleted_unused_files';

    public const PROCESS_FILES_PER_ITERATION = 100;

    public function __construct(
        private ConfigInterface $config,
        private FilesystemFactory $fileSystemFactory,
        private FileRepository $rFile,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    #[Transactional(attempts: 1)]
    public function run(): int
    {
        $clearedUnused = $this->processUnusedFiles();
        $clearedDeleted = $this->processDeletedFiles();
        $this->eventDispatcher->dispatch(new Action(self::ACTION));
        return $clearedUnused + $clearedDeleted;
    }

    private function processUnusedFiles(): int
    {
        $total = 0;
        $limitDate = Carbon::now()->subSeconds($this->config->get('file_upload.unused_file_max_lifetime'));
        while (
            $files = $this->rFile
            ->queryUnusedFilesOlderThen($limitDate)
            ->limit(self::PROCESS_FILES_PER_ITERATION)
            ->get()
            ->all()
        ) {
            $this->deleteFiles($files);
            $total += count($files);
        }
        return $total;
    }

    private function processDeletedFiles(): int
    {
        $total = 0;
        while (
            $files = $this->rFile
            ->queryDeletedFiles()
            ->limit(self::PROCESS_FILES_PER_ITERATION)
            ->get()
            ->all()
        ) {
            $this->deleteFiles($files);
            $total += count($files);
        }
        return $total;
    }

    private function deleteFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->deleteFile($file);
        }
    }

    private function deleteFile(File $file): void
    {
        $filesystem = $this->fileSystemFactory->get($file->storage);
        try {
            $filesystem->deleteDirectory($file->path);
        } catch (FilesystemException $e) {
            // do nothing
        } finally {
            $this->rFile->forceDelete($file);
        }
    }
}
