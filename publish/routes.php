<?php

declare(strict_types=1);
use Hyperf\HttpServer\Router\Router;
use OnixSystemsPHP\HyperfFileUpload\Controller\FileController;

Router::addGroup('/v1/file', function () {
    Router::post('', [FileController::class, 'create']);
    Router::post('/url', [FileController::class, 'createFromUrl']);
});
