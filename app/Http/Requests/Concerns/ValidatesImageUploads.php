<?php

namespace App\Http\Requests\Concerns;

use App\Models\Attachment;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

trait ValidatesImageUploads
{
    /**
     * The maximum number of images a post or comment may have.
     */
    protected int $maxImages = 10;

    /**
     * Rules for an optional `attachments[]` field of images up to 5 MB each.
     *
     * @return array<string, array<mixed>>
     */
    protected function imageUploadRules(): array
    {
        return [
            'attachments' => ['nullable', 'array', "max:{$this->maxImages}"],
            'attachments.*' => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ];
    }

    /**
     * Rules for updating a model's images: the images kept plus the new uploads must fit the
     * limit, and `remove_attachments[]` may only name the model's own attachments.
     *
     * @return array<string, array<mixed>>
     */
    protected function imageUpdateRules(Model $attachable): array
    {
        $rules = $this->imageUploadRules();

        $rules['attachments'][] = function (string $attribute, mixed $value, Closure $fail) use ($attachable) {
            if (! is_array($value)) {
                return;
            }

            $removing = $attachable->attachments()
                ->whereKey(array_filter(Arr::wrap($this->input('remove_attachments')), 'is_numeric'))
                ->count();

            if ($attachable->attachments()->count() - $removing + count($value) > $this->maxImages) {
                $fail("이미지는 기존 이미지를 포함해 최대 {$this->maxImages}장까지 첨부할 수 있습니다.");
            }
        };

        return [
            ...$rules,
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => [
                'integer',
                Rule::exists(Attachment::class, 'id')
                    ->where('attachable_type', $attachable->getMorphClass())
                    ->where('attachable_id', $attachable->getKey()),
            ],
        ];
    }
}
