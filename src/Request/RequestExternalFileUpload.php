<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfFileUpload\Request;

use Hyperf\Validation\Request\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RequestExternalFileUpload',
    properties: [new OA\Property(property: 'file', type: 'string', format: 'uri', default: 'https://picsum.photos/300/300')],
    type: 'object',
)]
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
