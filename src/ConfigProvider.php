<?php

declare(strict_types=1);

namespace OnixSystemsPHP\HyperfFileUpload;

class ConfigProvider
{
    public function __invoke(): array
    {
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
