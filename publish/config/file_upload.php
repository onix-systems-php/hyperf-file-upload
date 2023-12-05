<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use OnixSystemsPHP\HyperfCore\Constants\Time;

use function Hyperf\Support\env;

return [
    'mime_types' => [
        'image/png',
        'image/jpg',
        'image/jpeg',
        'image/bmp',
        'application/pdf',
    ],
    'image_mime_types' => [
        'image/png',
        'image/jpg',
        'image/jpeg',
        'image/bmp',
    ],
    'file_actions' => [
        'delete_it',
    ],
    'unused_file_max_lifetime' => Time::DAY,
    'storage' => [
        'local' => [
            'domain' => env('DOMAIN_API', null),
            'public_path_prefix' => DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads',
            'storage_path_prefix' => DIRECTORY_SEPARATOR . 'uploads',
        ],
        's3' => [
            'domain' => env('S3_DOMAIN'),
            'public_path_prefix' => DIRECTORY_SEPARATOR . 'uploads',
            'storage_path_prefix' => DIRECTORY_SEPARATOR . 'uploads',
        ],
    ],
];
