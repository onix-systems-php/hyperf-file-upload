<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfFileUpload\Test\Cases\Service;

use OnixSystemsPHP\HyperfFileUpload\Service\AddExternalFileService;
use OnixSystemsPHP\HyperfFileUpload\Contract\AddFileServiceInterface;
use OnixSystemsPHP\HyperfFileUpload\Service\DownloadFileService;
use OnixSystemsPHP\HyperfFileUpload\Test\Cases\AppTest;

/**
 * @internal
 * @coversNothing
 */
class AddExternalFileServiceTest extends AppTest
{
    protected function setUp(): void
    {
        $this->createContainer();
        parent::setUp();
    }

    public function testMain(): void
    {
        $fileName = $this->createFile();
        $service = $this->getService($fileName);
        $service->run('fakeUrl', null);
        $this->assertTrue(true);
        unlink($fileName);
    }

    public function testIfException(): void
    {
        $fileName = $this->createFile();
        $service = $this->getService($fileName, true);
        $this->expectException(\Exception::class);
        $service->run('fakeUrl', null);
        $this->assertFileDoesNotExist($fileName);
    }

    protected function getService(string $fileName, bool $expectException = false): AddExternalFileService
    {
        $downloadFileService = $this->createMock(DownloadFileService::class);
        $downloadFileService->expects($this->once())->method('run')->willReturn($fileName);
        $addFileService = $this->createMock(AddFileServiceInterface::class);
        if ($expectException) {
            $addFileService->expects($this->once())->method('run')->willThrowException(new \Exception());
        }
        return new AddExternalFileService(
            $downloadFileService,
            $addFileService,
        );
    }

    private function createFile(): string
    {
        $path = __DIR__ . '/../../Mocks/TestFile.txt';
        file_put_contents($path, 'Test');
        return $path;
    }
}
