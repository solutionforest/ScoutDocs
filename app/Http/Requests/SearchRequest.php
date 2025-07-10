<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
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
        return [
            'q' => [
                'required',
                'string',
                'min:2',
                'max:500'
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1'
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100'
            ],
            'file_type' => [
                'nullable',
                'array'
            ],
            'file_type.*' => [
                'string',
                'in:pdf,doc,docx,txt'
            ],
            'date_from' => [
                'nullable',
                'date',
                'before_or_equal:date_to'
            ],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from'
            ],
            'sort' => [
                'nullable',
                'string',
                'in:relevance,date_desc,date_asc,title_asc,title_desc,size_desc,size_asc'
            ]
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Search query is required.',
            'q.min' => 'Search query must be at least 2 characters long.',
            'q.max' => 'Search query must not exceed 500 characters.',
            'per_page.max' => 'Results per page cannot exceed 100.',
            'file_type.*.in' => 'Invalid file type. Supported types: pdf, doc, docx, txt.',
            'date_from.before_or_equal' => 'Start date must be before or equal to end date.',
            'date_to.after_or_equal' => 'End date must be after or equal to start date.',
            'sort.in' => 'Invalid sort option.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'page' => $this->page ?? 1,
            'per_page' => $this->per_page ?? 10,
            'sort' => $this->sort ?? 'relevance'
        ]);
    }
}
