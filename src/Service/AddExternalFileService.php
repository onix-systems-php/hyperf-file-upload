<?php

declare(strict_types=1);
namespace OnixSystemsPHP\HyperfFileUpload\Service;

use Hyperf\DbConnection\Annotation\Transactional;
use Hyperf\HttpMessage\Upload\UploadedFile;
use OnixSystemsPHP\HyperfCore\Contract\CoreAuthenticatable;
use OnixSystemsPHP\HyperfCore\Service\Service;
use OnixSystemsPHP\HyperfFileUpload\Model\File;

#[Service]
class AddExternalFileService
{
    public function __construct(
        private DownloadFileService $downloadFileService,
        private AddFileService $addFileService,
    ) {
    }

    #[Transactional(attempts: 1)]
    public function run(string $url, CoreAuthenticatable|null $user): File
    {
        $filename = $this->downloadFileService->run($url, $user);
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
