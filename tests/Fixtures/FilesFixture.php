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
namespace OnixSystemsPHP\HyperfFileUpload\Test\Fixtures;

class FilesFixture
{
    public static function image1(): array
    {
        return array_merge(self::schema(), [
            'mime' => 'image/jpeg',
        ]);
    }

    public static function document1(): array
    {
        return array_merge(self::schema(), [
            'name' => 'file1.pdf',
            'full_path' => '/path/to/file1.pdf',
            'url' => 'https://domain.com/path/to/file1.pdf',
            'original_name' => 'file1.pdf',
            'mime' => 'application/pdf',
        ]);
    }

    public static function document2(): array
    {
        return array_merge(self::schema(), [
            'name' => 'file2.pdf',
            'full_path' => '/path/to/file2.pdf',
            'url' => 'https://domain.com/path/to/file2.pdf',
            'original_name' => 'file2.pdf',
            'mime' => 'application/pdf',
        ]);
    }

    public static function document3(): array
    {
        return array_merge(self::schema(), [
            'name' => 'file3.pdf',
            'full_path' => '/path/to/file3.pdf',
            'url' => 'https://domain.com/path/to/file3.pdf',
            'original_name' => 'file3.pdf',
            'mime' => 'application/pdf',
        ]);
    }

    private static function schema()
    {
        return [
            'user_id' => null,
            'fileable_id' => null,
            'fileable_type' => null,
            'field_name' => null,
            'storage' => 'local',
            'path' => '/path/to',
            'name' => 'file.png',
            'full_path' => '/path/to/file.png',
            'domain' => 'https://domain.com',
            'url' => 'https://domain.com/path/to/file.png',
            'original_name' => 'file.png',
            'size' => '9999',
            'mime' => 'image/png',
            'presets' => [],
        ];
    }
}
