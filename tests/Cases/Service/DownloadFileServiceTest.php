<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfFileUpload\Test\Cases\Service;

use GuzzleHttp\Client;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Contract\ValidatorInterface;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidationException;
use OnixSystemsPHP\HyperfCore\Exception\BusinessException;
use OnixSystemsPHP\HyperfFileUpload\Service\DownloadFileService;
use OnixSystemsPHP\HyperfFileUpload\Test\Cases\AppTest;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;

/**
 * @internal
 * @coversNothing
 */
class DownloadFileServiceTest extends AppTest
{
    protected function setUp(): void
    {
        $trans = $this->createMock(TranslatorInterface::class);
        $this->createContainer([TranslatorInterface::class => $trans]);
        parent::setUp();
    }

    public function testMain()
    {
        $service = $this->getService(1, 1);
        $fileName = $service->run('fakeUrl');
        $this->assertIsString($fileName);
    }

    public function testIfErrorRequest()
    {
        $service = $this->getService(0, 1, expectException: true);
        $this->expectException(BusinessException::class);
        $service->run('fakeUrl');
        $this->assertTrue(true);
    }

    public function testUrlNotValid()
    {
        $service = $this->getService(0, 0, isValid: false);
        $this->expectException(ValidationException::class);
        $service->run('fakeUrl');
        $this->assertTrue(true);
    }

    protected function getService(
        int  $eventCount,
        int  $requestCount,
        bool $expectException = false,
        bool $isValid = true,
    ): DownloadFileService
    {
        $client = $this->createMock(Client::class);
        $client->expects(new InvokedCount($requestCount))->method('request');
        if ($expectException) {
            $client->method('request')->willThrowException(new BusinessException());
        }

        $clientFactory = $this->createMock(ClientFactory::class);
        $clientFactory->method('create')->willReturn($client);

        $validatorFactoryInterface = $this->createMock(ValidatorFactoryInterface::class);
        if (!$isValid) {
            $ValidatorInterface = $this->createMock(ValidatorInterface::class);
            $validatorFactoryInterface->method('make')->willThrowException(new ValidationException($ValidatorInterface));
        }

        return new DownloadFileService(
            $clientFactory,
            $validatorFactoryInterface,
            $this->getEventDispatcherMock($eventCount),
            null,
        );
    }
}
