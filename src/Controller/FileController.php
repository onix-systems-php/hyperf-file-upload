<?php

declare(strict_types=1);
namespace OnixSystemsPHP\HyperfFileUpload\Controller;

use OnixSystemsPHP\HyperfCore\Contract\CoreAuthenticatableProvider;
use OnixSystemsPHP\HyperfCore\Controller\AbstractController;
use OnixSystemsPHP\HyperfFileUpload\Request\RequestExternalFileUpload;
use OnixSystemsPHP\HyperfFileUpload\Request\RequestFileUpload;
use OnixSystemsPHP\HyperfFileUpload\Resource\ResourceFile;
use OnixSystemsPHP\HyperfFileUpload\Service\AddExternalFileService;
use OnixSystemsPHP\HyperfFileUpload\Service\AddFileService;
use OpenApi\Attributes as OA;

class FileController extends AbstractController
{
    public function __construct(
        private CoreAuthenticatableProvider $authenticatableProvider,
    ) {
    }


    #[OA\Post(
        path: '/v1/file',
        operationId: 'upload',
        summary: 'Upload file',
        requestBody: new OA\RequestBody(required: true, content: [
            new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/RequestFileUpload'))
        ]),
        tags: ['file'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/Locale')],
        responses: [
            new OA\Response(response: 200, description: '', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'string'),
                new OA\Property(property: 'data', ref: '#/components/schemas/ResourceFile'),
            ])),
            new OA\Response(response: 422, ref: '#/components/responses/422'),
            new OA\Response(response: 500, ref: '#/components/responses/500'),
        ],
    )]
    public function create(RequestFileUpload $request, AddFileService $addFileService): ResourceFile
    {
        $params = $request->validated();
        $file = $addFileService->run($params['file'], $this->authenticatableProvider->user());
        return new ResourceFile($file);
    }


    #[OA\Post(
        path: '/v1/file/url',
        operationId: 'uploadExternal',
        summary: 'Upload external file',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/RequestExternalFileUpload')),
        tags: ['file'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/Locale')],
        responses: [
            new OA\Response(response: 200, description: '', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'string'),
                new OA\Property(property: 'data', ref: '#/components/schemas/ResourceFile'),
            ])),
            new OA\Response(response: 400, ref: '#/components/responses/400'),
            new OA\Response(response: 422, ref: '#/components/responses/422'),
            new OA\Response(response: 500, ref: '#/components/responses/500'),
        ],
    )]
    public function createFromUrl(
        RequestExternalFileUpload $request,
        AddExternalFileService $addFileService,
    ): ResourceFile {
        $params = $request->validated();
        $file = $addFileService->run($params['file'], $this->authenticatableProvider->user());
        return new ResourceFile($file);
    }
}
