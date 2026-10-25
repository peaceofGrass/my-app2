<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesImageUploads;
use App\Models\Comment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    use ValidatesImageUploads;

    /**
     * Users may comment on any post they are allowed to view.
     */
    public function authorize(): bool
    {
        Gate::authorize('view', $this->route('post'));

        return true;
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
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(Comment::class, 'id')->where('post_id', $this->route('post')->id),
            ],
            ...$this->imageUploadRules(),
        ];
    }
}
