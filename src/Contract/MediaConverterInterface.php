<?php
declare(strict_types=1);

namespace OnixSystemsPHP\HyperfFileUpload\Contract;

use Hyperf\HttpMessage\Upload\UploadedFile;

interface MediaConverterInterface
{
    public function canConvert(string $mimeType): bool;
    public function canConvertByExtension(string $mimeType): bool;
    public function convert(UploadedFile $file): UploadedFile;
}
