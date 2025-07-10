<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\TextExtractorService;

class StoreDocumentRequest extends FormRequest
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
        $textExtractor = app(TextExtractorService::class);
        $supportedTypes = implode(',', $textExtractor->getSupportedTypes());
        $maxFileSize = $textExtractor->getMaxFileSize() / 1024; // Convert to KB for validation
        
        return [
            'file' => [
                'required',
                'file',
                "max:{$maxFileSize}",
                "mimes:{$supportedTypes}"
            ],
            'title' => [
                'nullable',
                'string',
                'min:3',
                'max:255'
            ]
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        $textExtractor = app(TextExtractorService::class);
        $supportedTypes = implode(', ', $textExtractor->getSupportedTypes());
        $maxSizeMB = $textExtractor->getMaxFileSize() / 1048576; // Convert to MB
        
        return [
            'file.required' => 'A document file is required.',
            'file.file' => 'The uploaded item must be a valid file.',
            'file.max' => "The file size must not exceed {$maxSizeMB}MB.",
            'file.mimes' => "The file must be one of the following types: {$supportedTypes}.",
            'title.min' => 'The title must be at least 3 characters long.',
            'title.max' => 'The title must not exceed 255 characters.',
        ];
    }
}
