<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesImageUploads;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    use ValidatesImageUploads;

    /**
     * Only the author may update the comment.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('comment'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
            ...$this->imageUpdateRules($this->route('comment')),
        ];
    }
}
