<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfFileUpload\Contract;

use Hyperf\HttpMessage\Upload\UploadedFile;
use OnixSystemsPHP\HyperfCore\Contract\CoreAuthenticatable;
use OnixSystemsPHP\HyperfFileUpload\Model\File;

interface AddFileServiceInterface
{
    public function run(UploadedFile $uploadedFile, ?CoreAuthenticatable $user): File;

    public function validate(UploadedFile $uploadedFile): void;

    public function storeFile(UploadedFile $uploadedFile, ?CoreAuthenticatable $user): File;

    public function generatePath(UploadedFile $file, string $storage): array;

    public function mimeToExt(string $mime): ?string;
}
