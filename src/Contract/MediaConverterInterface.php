<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfFileUpload\Contract;

use Hyperf\HttpMessage\Upload\UploadedFile;

interface MediaConverterInterface
{
    public function canConvert(string $mimeType, string $extension): bool;
    public function isVideo(string $mimeType, string $extension): bool;
    public function convertToMp4(UploadedFile $file): UploadedFile;
    public function convert(UploadedFile $file): UploadedFile;
}
