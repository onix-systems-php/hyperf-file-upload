<?php

declare(strict_types=1);
namespace OnixSystemsPHP\HyperfFileUpload\Request;

use Hyperf\Validation\Request\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RequestFileUpload",
 *     type="object",
 *     @OA\Property(property="file", type="string", format="binary"),
 * )
 * @OA\Schema(
 *     schema="RequestFileAssign",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="delete_it", type="boolean", default="false")
 * )
 */
class RequestFileUpload extends FormRequest
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
        $rule = 'required';
        return [
            'file' => $rule,
        ];
    }
}
