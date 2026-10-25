<?php

namespace App\Http\Requests\Concerns;

trait ValidatesImageUploads
{
    /**
     * The maximum number of images accepted in a single request.
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
}
