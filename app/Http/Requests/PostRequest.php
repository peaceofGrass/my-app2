<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesImageUploads;
use App\Models\Post;
use App\Support\PostContent;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    use ValidatesImageUploads;

    /**
     * Any authenticated user may create a post; only the author may update it.
     */
    public function authorize(): bool
    {
        $post = $this->route('post');

        return ! $post instanceof Post || $this->user()->can('update', $post);
    }

    /**
     * Sanitize the editor HTML up front, so validation and the controller only ever see safe markup.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('content'))) {
            $this->merge(['content' => PostContent::sanitize($this->input('content'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $post = $this->route('post');

        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => [
                'required',
                'string',
                'max:'.PostContent::MAX_LENGTH,
                function (string $attribute, string $value, Closure $fail) {
                    if (PostContent::isBlank($value)) {
                        $fail('validation.required')->translate();
                    }
                },
            ],
            'is_published' => ['required', 'boolean'],
            ...($post instanceof Post ? $this->imageUpdateRules($post) : $this->imageUploadRules()),
        ];
    }
}
