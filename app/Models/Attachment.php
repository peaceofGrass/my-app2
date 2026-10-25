<?php

namespace App\Models;

use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Fillable(['original_name', 'file_path', 'disk', 'file_size', 'mime_type'])]
#[Appends(['url'])]
class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The public URL of the file on its storage disk.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk($this->disk)->url($this->file_path));
    }

    /**
     * Delete the attachment rows now and their files once the surrounding transaction commits,
     * so a rolled-back delete never leaves rows pointing at missing files.
     *
     * @param  Collection<int, Attachment>  $attachments
     */
    public static function purge(Collection $attachments): void
    {
        if ($attachments->isEmpty()) {
            return;
        }

        static::whereKey($attachments->modelKeys())->delete();

        DB::afterCommit(fn () => $attachments->each(
            fn (Attachment $attachment) => Storage::disk($attachment->disk)->delete($attachment->file_path),
        ));
    }
}
