<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfFileUpload\Service;

use Hyperf\Guzzle\ClientFactory;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use OnixSystemsPHP\HyperfActionsLog\Event\Action;
use OnixSystemsPHP\HyperfCore\Contract\CoreAuthenticatable;
use OnixSystemsPHP\HyperfCore\Contract\CorePolicyGuard;
use OnixSystemsPHP\HyperfCore\Exception\BusinessException;
use OnixSystemsPHP\HyperfCore\Service\Service;
use OnixSystemsPHP\HyperfFileUpload\Model\File;
use Psr\EventDispatcher\EventDispatcherInterface;

use function Hyperf\Translation\__;

#[Service]
class DownloadFileService
{
    public const ACTION = 'download_file';

    public function __construct(
        private ClientFactory $clientFactory,
        private ValidatorFactoryInterface $vf,
        private EventDispatcherInterface $eventDispatcher,
        private ?CorePolicyGuard $policyGuard,
    ) {}

    public function run(string $url, null|CoreAuthenticatable $user = null, array $requestOptions = []): string
    {
        $this->validate($url);
        $this->policyGuard?->check('download', new File(), ['url' => $url]);
        $filename = tempnam(sys_get_temp_dir(), 'download_');
        $options = [];
        $client = $this->clientFactory->create($options);
        try {
            $requestOptions['sink'] = $filename;
            $client->request('GET', $url, $requestOptions);
        } catch (\Throwable) {
            throw new BusinessException(400, __('exceptions.file.download_issue'));
        }
        $this->eventDispatcher->dispatch(new Action(self::ACTION, null, ['url' => $url], $user));

        return $filename;
    }

    private function validate(string $url): void
    {
        $this->vf
            ->make(['url' => $url], [
                'url' => 'required|url',
            ])
            ->validate();
    }
}
