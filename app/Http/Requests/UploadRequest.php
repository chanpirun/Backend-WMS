<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'max:102400', // 100MB max
                'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,ppt,pptx,txt,zip,csv,json,xlsx,xls,sql,db',
            ],
            'resource' => 'sometimes|string|in:uploads,projects,submissions,documents,covers,datasets,source,finalized-documents,team_documents',
        ];
    }
}
