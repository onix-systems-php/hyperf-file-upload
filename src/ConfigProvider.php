<?php

declare(strict_types=1);
namespace OnixSystemsPHP\HyperfFileUpload;

use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Router\Router;
use Hyperf\Utils\ApplicationContext;

class ConfigProvider
{
    public function __invoke(): array
    {
        if (ApplicationContext::hasContainer()) {
            /** @var ConfigInterface $container */
            $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
            if (in_array('file-upload', $config->get('extensions', []))) {
                Router::addGroup('/v1/file', function () {
                    Router::post('', [Controller\FileController::class, 'create']);
                    Router::post('url', [Controller\FileController::class, 'createFromUrl']);
                });
            }
        }

        return [
            'dependencies' => [
            ],
            'commands' => [
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'migration',
                    'description' => 'The migration with files table for onix-systems-php/hyperf-file-upload.',
                    'source' => __DIR__ . '/../publish/migrations/2022_04_07_082256_files.php',
                    'destination' => BASE_PATH . '/migrations/2022_04_07_082256_files.php',
                ],
                [
                    'id' => 'config',
                    'description' => 'The config for onix-systems-php/hyperf-file-upload.',
                    'source' => __DIR__ . '/../publish/config/file_upload.php',
                    'destination' => BASE_PATH . '/config/autoload/file_upload.php',
                ],
            ],
        ];
    }
}
