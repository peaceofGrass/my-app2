<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostImageRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PostImageController extends Controller
{
    /**
     * Store an image uploaded from the post body editor.
     * It stays unattached until a post that embeds it is saved.
     */
    public function store(StorePostImageRequest $request): JsonResponse
    {
        $file = $request->file('image');
        $path = $file->store('post-images', 'public');

        if ($path === false) {
            throw new RuntimeException("Failed to store [{$file->getClientOriginalName()}].");
        }

        $image = $request->user()->postImages()->create([
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'disk' => 'public',
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return response()->json([
            'id' => $image->id,
            'url' => $image->url,
        ], 201);
    }
}
