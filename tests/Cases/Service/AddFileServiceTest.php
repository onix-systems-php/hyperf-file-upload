<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfFileUpload\Test\Cases\Service;

use Hyperf\Config\Config;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\HttpMessage\Upload\UploadedFile;
use League\Flysystem\Filesystem;
use OnixSystemsPHP\HyperfCore\Contract\CoreAuthenticatable;
use OnixSystemsPHP\HyperfCore\Exception\BusinessException;
use OnixSystemsPHP\HyperfFileUpload\Repository\FileRepository;
use OnixSystemsPHP\HyperfFileUpload\Service\AddFileService;
use OnixSystemsPHP\HyperfFileUpload\Test\Cases\AppTest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;

/**
 * @internal
 * @coversNothing
 */
class AddFileServiceTest extends AppTest
{
    public CoreAuthenticatable $user;

    protected function setUp(): void
    {
        parent::setUp();

        $trans = $this->createMock(TranslatorInterface::class);
        $this->createContainer([TranslatorInterface::class => $trans]);
        $this->user = $this->createMock(CoreAuthenticatable::class);
        $this->user->method('getId')->willReturn(1);
    }

    public function testMain(): void
    {
        $service = $this->getContainer(1, 1, 1, 1);
        $file = $service->run(
            new UploadedFile(BASE_PATH . '/tests/test-image.png', 70, UPLOAD_ERR_OK, 'test-image.png', 'image/png'),
            $this->user
        );
        $this->assertEquals($this->user->getId(), $file->user_id);
        $this->assertNull($file->fileable_id);
        $this->assertNull($file->fileable_type);
        $this->assertNull($file->field_name);
        $this->assertNotNull($file->storage);
        $this->assertNotNull($file->path);
        $this->assertNotNull($file->name);
        $this->assertNotNull($file->full_path);
        $this->assertNotNull($file->domain);
        $this->assertNotNull($file->url);
        $this->assertEquals('test-image.png', $file->original_name);
        $this->assertEquals(70, $file->size);
        $this->assertEquals('image/png', $file->mime);
        $this->assertEquals([], $file->presets);
    }

    public function testNotLoggedIn(): void
    {
        $service = $this->getContainer(1, 1, 1, 1);
        $file = $service->run(
            new UploadedFile(BASE_PATH . '/tests/test-image.png', 70, UPLOAD_ERR_OK, 'test-image.png', 'image/png'),
            null
        );
        $this->assertEquals(null, $file->user_id);
    }

    public function testUploadError(): void
    {
        /** @var AddFileService $service */
        $service = $this->getContainer(0, 0, 0, 0);
        $this->expectException(BusinessException::class);
        $service->run(
            new UploadedFile(BASE_PATH . '/tests/test-image.png', 70, UPLOAD_ERR_NO_TMP_DIR, 'test-image.png', 'image/png'),
            $this->user
        );
    }

    public function testWrongMimeType(): void
    {
        $service = $this->getContainer(0, 0, 0, 0);
        $this->expectException(BusinessException::class);
        $service->run(
            new UploadedFile(BASE_PATH . '/tests/bootstrap.php', 1073, UPLOAD_ERR_OK, 'test-image.png', 'image/png'),
            $this->user
        );
    }

    protected function getContainer(int $nEvents, int $writesCount, int $savesCount, $createsCount, ?string $converterClass = null): AddFileService
    {
        $fileSystemMock = $this->createMock(Filesystem::class);
        $fileSystemMock->expects(new InvokedCount($writesCount))->method('writeStream');

        $fileSystemFactoryMock = $this->createMock(FilesystemFactory::class);
        $fileSystemFactoryMock->method('get')->willReturn($fileSystemMock);
        $containerMock = $this->createMock(ContainerInterface::class);
        return new AddFileService(
            $this->getConfig(),
            $fileSystemFactoryMock,
            $this->getRepository($savesCount, $createsCount),
            $this->getEventDispatcherMock($nEvents),
            $containerMock,
            null,
        );
    }

    private function getConfig(): Config
    {
        $config = new Config([]);
        $config->set('file_upload.mime_types', ['image/png', 'image/jpg', 'image/jpeg', 'image/bmp', 'application/pdf']);
        $config->set('file.default', 'local');
        $config->set('file_upload.storage.local.domain', 'fake');
        return $config;
    }

    private function getRepository(int $savesCount, int $createsCount): FileRepository|MockObject
    {
        $originalRepository = new FileRepository(null);
        $repository = $this->createMock(FileRepository::class);
        $repository->expects(new InvokedCount($savesCount))->method('save');
        $repository->expects(new InvokedCount($createsCount))
            ->method('create')
            ->willReturnCallback(fn ($arg) => $originalRepository->create($arg));
        return $repository;
    }
}
