<?php

declare(strict_types=1);
namespace OnixSystemsPHP\HyperfFileUpload\Controller;

use App\Auth\SessionManager;
use Hyperf\HttpServer\Annotation\PostMapping;
use OnixSystemsPHP\HyperfCore\Controller\AbstractController;
use OnixSystemsPHP\HyperfFileUpload\Request\RequestExternalFileUpload;
use OnixSystemsPHP\HyperfFileUpload\Request\RequestFileUpload;
use OnixSystemsPHP\HyperfFileUpload\Resource\ResourceFile;
use OnixSystemsPHP\HyperfFileUpload\Service\AddExternalFileService;
use OnixSystemsPHP\HyperfFileUpload\Service\AddFileService;
use OpenApi\Annotations as OA;

class FileController extends AbstractController
{
    public function __construct(
        private SessionManager $sessionManager,
    ) {
    }

    /**
     * @OA\Post(
     *     path="/v1/file",
     *     summary="Upload file",
     *     operationId="upload",
     *     tags={"file"},
     *     @OA\Parameter(ref="#/components/parameters/Locale"),
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(ref="#/components/schemas/RequestFileUpload"))),
     *     @OA\Response(response=200, description="", @OA\JsonContent(
     *         @OA\Property(property="status", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/ResourceFile"),
     *     )),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function create(RequestFileUpload $request, AddFileService $addFileService): ResourceFile
    {
        $params = $request->validated();
        $file = $addFileService->run($params['file'], $this->sessionManager->user());
        return new ResourceFile($file);
    }

    /**
     * @OA\Post(
     *  path="/v1/file/url",
     *  summary="Upload external file",
     *  operationId="uploadExternal",
     *  tags={"file"},
     *  @OA\Parameter(ref="#/components/parameters/Locale"),
     *  @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/RequestExternalFileUpload")),
     *  @OA\Response(response=200, description="", @OA\JsonContent(
     *    @OA\Property(property="status", type="string"),
     *    @OA\Property(property="data", ref="#/components/schemas/ResourceFile"),
     *  )),
     *  @OA\Response(response=400, ref="#/components/responses/400"),
     *  @OA\Response(response=422, ref="#/components/responses/422"),
     *  @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    #[PostMapping(path: 'url')]
    public function createFromUrl(
        RequestExternalFileUpload $request,
        AddExternalFileService $addFileService,
    ): ResourceFile {
        $params = $request->validated();
        $file = $addFileService->run($params['file'], $this->sessionManager->user());
        return new ResourceFile($file);
    }
}
