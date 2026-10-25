<?php

namespace App\Models\Concerns;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use RuntimeException;

trait HasAttachments
{
    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Store the uploaded files on the given disk and record them as attachments.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function storeAttachments(array $files, string $disk = 'public'): void
    {
        foreach ($files as $file) {
            $path = $file->store('attachments', $disk);

            if ($path === false) {
                throw new RuntimeException("Failed to store [{$file->getClientOriginalName()}].");
            }

            $this->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'disk' => $disk,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        }
    }
}
