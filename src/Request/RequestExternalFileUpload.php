<?php

declare(strict_types=1);
namespace OnixSystemsPHP\HyperfFileUpload\Request;

use Hyperf\Validation\Request\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RequestExternalFileUpload",
 *     type="object",
 *     @OA\Property(property="file", type="string", format="uri", default="https://picsum.photos/300/300"),
 * )
 */
class RequestExternalFileUpload extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rule = 'required|string';
        return [
            'file' => $rule,
        ];
    }
}
