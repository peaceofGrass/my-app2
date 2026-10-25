<?php

namespace App\Http\Requests;

use App\Enums\ReactionType;
use App\Models\Comment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReactionRequest extends FormRequest
{
    /**
     * Users may react to any post they are allowed to view, and to the comments on it.
     */
    public function authorize(): bool
    {
        $comment = $this->route('comment');

        Gate::authorize('view', $comment instanceof Comment ? $comment->post : $this->route('post'));

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
            'type' => ['required', Rule::enum(ReactionType::class)],
        ];
    }
}
