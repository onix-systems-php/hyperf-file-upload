<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfFileUpload\Service;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\DbConnection\Annotation\Transactional;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Hyperf\Stringable\Str;
use OnixSystemsPHP\HyperfActionsLog\Event\Action;
use OnixSystemsPHP\HyperfCore\Contract\CoreAuthenticatable;
use OnixSystemsPHP\HyperfCore\Contract\CorePolicyGuard;
use OnixSystemsPHP\HyperfCore\Exception\BusinessException;
use OnixSystemsPHP\HyperfCore\Service\Service;
use OnixSystemsPHP\HyperfFileUpload\Contract\AddFileServiceInterface;
use OnixSystemsPHP\HyperfFileUpload\Contract\MediaConverterInterface;
use OnixSystemsPHP\HyperfFileUpload\Model\File;
use OnixSystemsPHP\HyperfFileUpload\Repository\FileRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

use function Hyperf\Translation\__;

#[Service]
class AddFileService implements AddFileServiceInterface
{
    public const ACTION = 'upload_file';

    public function __construct(
        private ConfigInterface $config,
        private FilesystemFactory $fileSystemFactory,
        private FileRepository $rFile,
        private EventDispatcherInterface $eventDispatcher,
        private ContainerInterface $container,
        private ?CorePolicyGuard $policyGuard,
    ) {}

    #[Transactional(attempts: 1)]
    public function run(UploadedFile $uploadedFile, ?CoreAuthenticatable $user): File
    {
        $this->validate($uploadedFile);
        $converterClass = $this->config->get("file_upload.file_converters.{$uploadedFile->getMimeType()}");
        if ($converterClass && $this->container->has($converterClass)) {
            /** @var MediaConverterInterface $converter */
            $converter = $this->container->get($converterClass);
            if (
                $converter->canConvert($uploadedFile->getMimeType(), $uploadedFile->getExtension())
            ) {
                $uploadedFile = $converter->convert($uploadedFile);
            } elseif (
                $converter->isVideo($uploadedFile->getMimeType(), $uploadedFile->getExtension())
            ) {
                $uploadedFile = $converter->convertToMp4($uploadedFile);
            }
        }
        $file = $this->storeFile($uploadedFile, $user);
        $this->eventDispatcher->dispatch(new Action(self::ACTION, $file, ['file' => $file->url], $user));
        return $file;
    }
    public function validate(UploadedFile $uploadedFile): void
    {
        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new BusinessException(422, __('exceptions.file.upload_issue'));
        }
        $allowedMimeTypes = $this->config->get('file_upload.mime_types', []);
        if (! in_array($uploadedFile->getMimeType(), $allowedMimeTypes)) {
            throw new BusinessException(422, __('exceptions.file.mime_type_issue'));
        }
    }

    public function storeFile(UploadedFile $uploadedFile, ?CoreAuthenticatable $user): File
    {
        $storage = $this->config->get('file.default');
        $domain = $this->config->get("file_upload.storage.{$storage}.domain");

        $this->policyGuard?->check(
            'create',
            new File(),
            ['size' => $uploadedFile->getSize(), 'mime' => $uploadedFile->getMimeType()]
        );

        [$originalName, $baseName, $path, $fullPath, $publicPath] = $this->generatePath($uploadedFile, $storage);
        $filesystem = $this->fileSystemFactory->get($storage);
        $filesystem->writeStream($fullPath, $uploadedFile->getStream()->detach(), []);
        $file = $this->rFile->create([
            'user_id' => $user?->getId(),
            'storage' => $storage,
            'path' => $path,
            'name' => $baseName,
            'full_path' => $fullPath,
            'domain' => $domain,
            'url' => "{$domain}{$publicPath}",
            'original_name' => $originalName,
            'size' => $uploadedFile->getSize(),
            'mime' => $uploadedFile->getMimeType(),
            'presets' => [],
        ]);
        $this->rFile->save($file);
        return $file;
    }

    public function generatePath(UploadedFile $file, string $storage): array
    {
        $originalName = empty($file->getClientFilename()) ? Str::random() : $file->getClientFilename();
        $name = Str::slug($originalName, '.');

        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $mimeExtension = $this->mimeToExt($file->getMimeType());
        if (! empty($mimeExtension) && (empty($extension) || $extension != $mimeExtension)) {
            $name .= '.' . $mimeExtension;
        }
        $directoryName = uniqid(more_entropy: true);

        $publicPathPrefix = $this->config->get("file_upload.storage.{$storage}.public_path_prefix", '');
        $storagePathPrefix = $this->config->get("file_upload.storage.{$storage}.storage_path_prefix", '');

        $path = $storagePathPrefix . DIRECTORY_SEPARATOR . $directoryName;
        $fullPath = $path . DIRECTORY_SEPARATOR . $name;
        $publicPath = $publicPathPrefix . DIRECTORY_SEPARATOR . $directoryName . DIRECTORY_SEPARATOR . $name;

        return [$originalName, $name, $path, $fullPath, $publicPath];
    }

    // https://gist.github.com/alexcorvi/df8faecb59e86bee93411f6a7967df2c
    public function mimeToExt(string $mime): ?string
    {
        $mime_map = [
            'video/3gpp2' => '3g2',
            'video/3gp' => '3gp',
            'video/3gpp' => '3gp',
            'application/x-compressed' => '7zip',
            'audio/x-acc' => 'aac',
            'audio/ac3' => 'ac3',
            'application/postscript' => 'ai',
            'audio/x-aiff' => 'aif',
            'audio/aiff' => 'aif',
            'audio/x-au' => 'au',
            'video/x-msvideo' => 'avi',
            'video/msvideo' => 'avi',
            'video/avi' => 'avi',
            'application/x-troff-msvideo' => 'avi',
            'application/macbinary' => 'bin',
            'application/mac-binary' => 'bin',
            'application/x-binary' => 'bin',
            'application/x-macbinary' => 'bin',
            'image/bmp' => 'bmp',
            'image/x-bmp' => 'bmp',
            'image/x-bitmap' => 'bmp',
            'image/x-xbitmap' => 'bmp',
            'image/x-win-bitmap' => 'bmp',
            'image/x-windows-bmp' => 'bmp',
            'image/ms-bmp' => 'bmp',
            'image/x-ms-bmp' => 'bmp',
            'application/bmp' => 'bmp',
            'application/x-bmp' => 'bmp',
            'application/x-win-bitmap' => 'bmp',
            'application/cdr' => 'cdr',
            'application/coreldraw' => 'cdr',
            'application/x-cdr' => 'cdr',
            'application/x-coreldraw' => 'cdr',
            'image/cdr' => 'cdr',
            'image/x-cdr' => 'cdr',
            'zz-application/zz-winassoc-cdr' => 'cdr',
            'application/mac-compactpro' => 'cpt',
            'application/pkix-crl' => 'crl',
            'application/pkcs-crl' => 'crl',
            'application/x-x509-ca-cert' => 'crt',
            'application/pkix-cert' => 'crt',
            'text/css' => 'css',
            'text/x-comma-separated-values' => 'csv',
            'text/comma-separated-values' => 'csv',
            'application/vnd.msexcel' => 'csv',
            'application/x-director' => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/x-dvi' => 'dvi',
            'message/rfc822' => 'eml',
            'application/x-msdownload' => 'exe',
            'video/x-f4v' => 'f4v',
            'audio/x-flac' => 'flac',
            'video/x-flv' => 'flv',
            'image/gif' => 'gif',
            'application/gpg-keys' => 'gpg',
            'application/x-gtar' => 'gtar',
            'application/x-gzip' => 'gzip',
            'application/mac-binhex40' => 'hqx',
            'application/mac-binhex' => 'hqx',
            'application/x-binhex40' => 'hqx',
            'application/x-mac-binhex40' => 'hqx',
            'text/html' => 'html',
            'image/x-icon' => 'ico',
            'image/x-ico' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'text/calendar' => 'ics',
            'application/java-archive' => 'jar',
            'application/x-java-application' => 'jar',
            'application/x-jar' => 'jar',
            'image/jp2' => 'jp2',
            'video/mj2' => 'jp2',
            'image/jpx' => 'jp2',
            'image/jpm' => 'jp2',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            'image/jpeg' => 'jpeg',
            'image/pjpeg' => 'jpeg',
            'application/x-javascript' => 'js',
            'application/json' => 'json',
            'text/json' => 'json',
            'application/vnd.google-earth.kml+xml' => 'kml',
            'application/vnd.google-earth.kmz' => 'kmz',
            'text/x-log' => 'log',
            'audio/x-m4a' => 'm4a',
            'application/vnd.mpegurl' => 'm4u',
            'audio/midi' => 'mid',
            'application/vnd.mif' => 'mif',
            'video/quicktime' => 'mov',
            'video/x-sgi-movie' => 'movie',
            'audio/mpeg' => 'mp3',
            'audio/mpg' => 'mp3',
            'audio/mpeg3' => 'mp3',
            'audio/mp3' => 'mp3',
            'video/mp4' => 'mp4',
            'video/mpeg' => 'mpeg',
            'application/oda' => 'oda',
            'application/vnd.oasis.opendocument.text' => 'odt',
            'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
            'application/vnd.oasis.opendocument.presentation' => 'odp',
            'audio/ogg' => 'ogg',
            'video/ogg' => 'ogg',
            'application/ogg' => 'ogg',
            'application/x-pkcs10' => 'p10',
            'application/pkcs10' => 'p10',
            'application/x-pkcs12' => 'p12',
            'application/x-pkcs7-signature' => 'p7a',
            'application/pkcs7-mime' => 'p7c',
            'application/x-pkcs7-mime' => 'p7c',
            'application/x-pkcs7-certreqresp' => 'p7r',
            'application/pkcs7-signature' => 'p7s',
            'application/pdf' => 'pdf',
            'application/octet-stream' => 'pdf',
            'application/x-x509-user-cert' => 'pem',
            'application/x-pem-file' => 'pem',
            'application/pgp' => 'pgp',
            'application/x-httpd-php' => 'php',
            'application/php' => 'php',
            'application/x-php' => 'php',
            'text/php' => 'php',
            'text/x-php' => 'php',
            'application/x-httpd-php-source' => 'php',
            'image/png' => 'png',
            'image/x-png' => 'png',
            'application/powerpoint' => 'ppt',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.ms-office' => 'ppt',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop' => 'psd',
            'image/vnd.adobe.photoshop' => 'psd',
            'audio/x-realaudio' => 'ra',
            'audio/x-pn-realaudio' => 'ram',
            'application/x-rar' => 'rar',
            'application/rar' => 'rar',
            'application/x-rar-compressed' => 'rar',
            'audio/x-pn-realaudio-plugin' => 'rpm',
            'application/x-pkcs7' => 'rsa',
            'text/rtf' => 'rtf',
            'text/richtext' => 'rtx',
            'video/vnd.rn-realvideo' => 'rv',
            'application/x-stuffit' => 'sit',
            'application/smil' => 'smil',
            'text/srt' => 'srt',
            'image/svg+xml' => 'svg',
            'application/x-shockwave-flash' => 'swf',
            'application/x-tar' => 'tar',
            'application/x-gzip-compressed' => 'tgz',
            'image/tiff' => 'tiff',
            'text/plain' => 'txt',
            'text/x-vcard' => 'vcf',
            'application/videolan' => 'vlc',
            'text/vtt' => 'vtt',
            'audio/x-wav' => 'wav',
            'audio/wave' => 'wav',
            'audio/wav' => 'wav',
            'application/wbxml' => 'wbxml',
            'video/webm' => 'webm',
            'audio/x-ms-wma' => 'wma',
            'application/wmlc' => 'wmlc',
            'video/x-ms-wmv' => 'wmv',
            'video/x-ms-asf' => 'wmv',
            'application/xhtml+xml' => 'xhtml',
            'application/excel' => 'xl',
            'application/msexcel' => 'xls',
            'application/x-msexcel' => 'xls',
            'application/x-ms-excel' => 'xls',
            'application/x-excel' => 'xls',
            'application/x-dos_ms_excel' => 'xls',
            'application/xls' => 'xls',
            'application/x-xls' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xlsx',
            'application/xml' => 'xml',
            'text/xml' => 'xml',
            'text/xsl' => 'xsl',
            'application/xspf+xml' => 'xspf',
            'application/x-compress' => 'z',
            'application/x-zip' => 'zip',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'application/s-compressed' => 'zip',
            'multipart/x-zip' => 'zip',
            'text/x-scriptzsh' => 'zsh',
        ];

        return array_key_exists($mime, $mime_map) ? $mime_map[$mime] : null;
    }
}
