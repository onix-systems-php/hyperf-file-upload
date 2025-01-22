<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfFileUpload\Service;

use Hyperf\HttpMessage\Upload\UploadedFile;
use OnixSystemsPHP\HyperfFileUpload\Contract\MediaConverterInterface;

class HeicConvertor implements MediaConverterInterface
{
    public function canConvert(string $mimeType): bool
    {
        return in_array($mimeType, ['image/heic', 'image/heif']);
    }

    public function canConvertByExtension(string $mimeType): bool
    {
        return in_array(strtolower($mimeType), ['heic', 'heif']);
    }

    public function convert(UploadedFile $file): UploadedFile
    {
        [$heicFile, $newFile, $simple] = $this->fileName($file, '.jpeg');
        $file->moveTo($heicFile);

        exec('magick convert ' . escapeshellarg($heicFile) . ' ' . escapeshellarg($newFile));

        if (! file_exists($newFile)) {
            throw new \RuntimeException("ImageMagick conversion failed: {$heicFile} to {$newFile}");
        }

        $fileSize = filesize($newFile);

        return new UploadedFile($newFile, $fileSize, UPLOAD_ERR_OK, $simple, 'image/jpeg');
    }

    private function fileName(UploadedFile $file, string $ext = '.jpg'): array
    {
        $originalName = pathinfo($file->getClientFilename(), PATHINFO_FILENAME);
        $path = $file->getPath();
        $heicFile = $path . $originalName . '.' . $file->getExtension();
        $newFile = $path . $originalName . $ext;

        return [$heicFile, $newFile, $originalName . $ext];
    }
}
