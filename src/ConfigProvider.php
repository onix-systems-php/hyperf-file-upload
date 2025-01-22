<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfFileUpload;

use OnixSystemsPHP\HyperfFileUpload\Contract\AddFileServiceInterface;
use OnixSystemsPHP\HyperfFileUpload\Contract\MediaConverterInterface;
use OnixSystemsPHP\HyperfFileUpload\Service\AddFileService;
use OnixSystemsPHP\HyperfFileUpload\Service\HeicConvertor;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                AddFileServiceInterface::class => AddFileService::class,
                MediaConverterInterface::class => HeicConvertor::class,
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
