<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfFileUpload\Test\Cases\Service;

use Carbon\Carbon;
use Hyperf\Config\Config;
use Hyperf\Filesystem\FilesystemFactory;
use League\Flysystem\Filesystem;
use OnixSystemsPHP\HyperfCore\Constants\Time;
use OnixSystemsPHP\HyperfCore\Contract\CoreAuthenticatable;
use OnixSystemsPHP\HyperfFileUpload\Model\File;
use OnixSystemsPHP\HyperfFileUpload\Repository\FileRepository;
use OnixSystemsPHP\HyperfFileUpload\Service\ClearUnusedDeletedFilesService;
use OnixSystemsPHP\HyperfFileUpload\Test\Cases\AppTest;
use OnixSystemsPHP\HyperfFileUpload\Test\Fixtures\FilesFixture;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;

/**
 * @internal
 * @coversNothing
 */
class ClearUnusedDeletedFilesServiceTest extends AppTest
{
    private array $files;

    private ?array $finderResult = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createContainer();

        $this->user = $this->user = $this->createMock(CoreAuthenticatable::class);
        $this->user->method('getId')->willReturn(1);

        $this->files = $this->fillFiles();
    }

    public function testMain(): void
    {
        $service = $this->getContainer(1, 3);
        $this->assertEquals(3, $service->run());
        $this->assertEquals(3, count($this->files));
    }

    protected function getContainer(int $nEvents, int $nDirectoryDelete): ClearUnusedDeletedFilesService
    {
        $fileSystemMock = $this->createMock(Filesystem::class);
        $fileSystemMock->expects(new InvokedCount($nDirectoryDelete))->method('deleteDirectory');
        $fileSystemFactoryMock = $this->createMock(FilesystemFactory::class);
        $fileSystemFactoryMock->method('get')->willReturn($fileSystemMock);
        return new ClearUnusedDeletedFilesService(
            $this->getConfig(),
            $fileSystemFactoryMock,
            $this->getRepository(),
            $this->getEventDispatcherMock($nEvents),
        );
    }

    private function getConfig(): Config
    {
        $config = new Config([]);
        $config->set('file_upload.unused_file_max_lifetime', Time::DAY);
        return $config;
    }

    private function getRepository(): FileRepository|MockObject
    {
        $repository = $this->getMockBuilder(FileRepository::class)
            ->setConstructorArgs([null])
            ->addMethods(['limit', 'get', 'all'])
            ->onlyMethods(['finder', 'forceDelete'])
            ->getMock();

        $repository->method('limit')->willReturn($repository);
        $repository->method('get')->willReturn($repository);
        $repository->method('get')->willReturn($repository);

        $repository->method('all')
            ->willReturnCallback(fn () => $this->finderResult);

        $repository->method('finder')
            ->willReturnCallback(fn ($arg1, $arg2 = null) => $this->finder($repository, $arg1, $arg2));

        $repository->method('forceDelete')
            ->willReturnCallback(fn ($arg) => $this->deleteFile($arg));

        return $repository;
    }

    private function fillFiles(): array
    {
        $files['fileAssignedInLoyalPeriod'] = new File(array_merge(FilesFixture::image1(), [
            'id' => 1,
            'user_id' => $this->user->getId(),
            'fileable_id' => 1,
            'fileable_type' => 'App\Model\SomeModel',
            'field_name' => 'field',
        ]));

        $files['fileAssignedInLoyalPeriod']->setDateFormat('Y-m-d h:i:s');
        $files['fileAssignedInLoyalPeriod']->created_at = Carbon::now()->subHours(20);

        $files['fileAssignedNotInLoyalPeriod'] = new File(array_merge(FilesFixture::image1(), [
            'id' => 2,
            'user_id' => $this->user->getId(),
            'fileable_id' => 1,
            'fileable_type' => 'App\Model\SomeModel',
            'field_name' => 'field',
        ]));

        $files['fileAssignedNotInLoyalPeriod']->setDateFormat('Y-m-d h:i:s');
        $files['fileAssignedNotInLoyalPeriod']->created_at = Carbon::now()->subHours(25);

        $files['fileNotAssignedInLoyalPeriod'] = new File(array_merge(FilesFixture::image1(), [
            'id' => 3,
            'user_id' => $this->user->getId(),
        ]));

        $files['fileNotAssignedInLoyalPeriod']->setDateFormat('Y-m-d h:i:s');
        $files['fileNotAssignedInLoyalPeriod']->created_at = Carbon::now()->subHours(10);

        $files['fileNotAssignedNotInLoyalPeriod'] = new File(array_merge(FilesFixture::image1(), [
            'id' => 4,
            'user_id' => $this->user->getId(),
        ]));
        $files['fileNotAssignedNotInLoyalPeriod']->setDateFormat('Y-m-d h:i:s');
        $files['fileNotAssignedNotInLoyalPeriod']->created_at = Carbon::now()->subHours(25);

        $files['fileAssignedDeleted'] = new File(array_merge(FilesFixture::image1(), [
            'id' => 5,
            'user_id' => $this->user->getId(),
            'fileable_id' => 1,
            'fileable_type' => 'App\Model\SomeModel',
            'field_name' => 'field',
        ]));
        $files['fileAssignedDeleted']->setDateFormat('Y-m-d h:i:s');
        $files['fileAssignedDeleted']->created_at = Carbon::now()->subHours(1);

        $files['fileNotAssignedDeleted'] = new File(array_merge(FilesFixture::image1(), [
            'id' => 6,
            'user_id' => $this->user->getId(),
            'path' => 'errorPath',
        ]));
        $files['fileNotAssignedDeleted']->setDateFormat('Y-m-d h:i:s');
        $files['fileNotAssignedDeleted']->created_at = Carbon::now()->subHours(1);
        $files['fileNotAssignedDeleted']->deleted_at = Carbon::now()->subHours(1);

        return $files;
    }

    private function finder(FileRepository $repository, string $arg1, ?Carbon $arg2 = null): FileRepository|MockObject
    {
        if ($arg1 === 'olderThan' && ! empty($arg2)) {
            foreach ($this->files as $file) {
                if ($file->created_at > $arg2) {
                    $files[] = $file;
                }
            }
            $this->finderResult = $files ?? [];
        }
        return $repository;
    }

    private function deleteFile(File $file): bool
    {
        $this->files = array_filter($this->files, fn (File $f) => $file->id !== $f->id);
        return true;
    }
}
