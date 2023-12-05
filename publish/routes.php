<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\HttpServer\Router\Router;
use OnixSystemsPHP\HyperfFileUpload\Controller\FileController;

Router::addGroup('/v1/file', function () {
    Router::post('', [FileController::class, 'create']);
    Router::post('/url', [FileController::class, 'createFromUrl']);
});
