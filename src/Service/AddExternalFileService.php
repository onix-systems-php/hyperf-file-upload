<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfFileUpload\Service;

use Hyperf\DbConnection\Annotation\Transactional;
use Hyperf\HttpMessage\Upload\UploadedFile;
use OnixSystemsPHP\HyperfCore\Contract\CoreAuthenticatable;
use OnixSystemsPHP\HyperfCore\Service\Service;
use OnixSystemsPHP\HyperfFileUpload\Contract\AddFileServiceInterface;
use OnixSystemsPHP\HyperfFileUpload\Model\File;

#[Service]
class AddExternalFileService
{
    public function __construct(
        private DownloadFileService $downloadFileService,
        private AddFileServiceInterface $addFileService,
    ) {}

    #[Transactional(attempts: 1)]
    public function run(string $url, null|CoreAuthenticatable $user, array $requestOptions = []): File
    {
        $filename = $this->downloadFileService->run($url, $user, $requestOptions);
        $size = filesize($filename);
        $basename = pathinfo($url, PATHINFO_BASENAME);
        $uploadedFile = new UploadedFile($filename, $size, UPLOAD_ERR_OK, $basename);
        try {
            $file = $this->addFileService->run($uploadedFile, $user);
        } catch (\Throwable $e) {
            unlink($filename);
            throw $e;
        }
        return $file;
    }
}
