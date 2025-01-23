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

class MediaConvertor implements MediaConverterInterface
{
    public function canConvert(string $mimeType, string $extension): bool
    {
        return in_array($mimeType, ['image/heic', 'image/heif']) && in_array(strtolower($extension), ['heic', 'heif']);
    }

    public function isVideo(string $mimeType, string $extension): bool
    {
        return in_array($mimeType, ['application/octet-stream']) && in_array(strtolower($extension), ['hevc']);
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

    public function convertToMp4(UploadedFile $file): UploadedFile
    {
        [$inputFile, $outputFile, $simpleName] = $this->fileName($file, '.mp4');
        $file->moveTo($inputFile);

        $command = sprintf(
            'ffmpeg -y -i %s -c:v libx264 -preset medium -crf 23 -c:a aac -b:a 128k %s',
            escapeshellarg($inputFile),
            escapeshellarg($outputFile)
        );

        exec($command, $output, $returnVar);

        $fileSize = filesize($outputFile);

        return new UploadedFile($outputFile, $fileSize, UPLOAD_ERR_OK, $simpleName, 'video/mp4');
    }

    private function fileName(UploadedFile $file, string $ext): array
    {
        $originalName = pathinfo($file->getClientFilename(), PATHINFO_FILENAME);
        $path = $file->getPath();
        $originalExtension = '.' . strtolower($file->getExtension());
        $tempFile = $path . DIRECTORY_SEPARATOR . $originalName . $originalExtension;
        $convertedFile = $path . DIRECTORY_SEPARATOR . $originalName . $ext;

        return [$tempFile, $convertedFile, $originalName . $ext];
    }
}
