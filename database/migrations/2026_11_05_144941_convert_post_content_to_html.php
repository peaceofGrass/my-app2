<?php

use App\Support\PostContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bodies were plain text before the Tiptap editor; it now renders as HTML,
 * so escape the old text and wrap it in paragraphs.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('posts')->orderBy('id')->chunkById(100, function ($posts) {
            foreach ($posts as $post) {
                DB::table('posts')->where('id', $post->id)->update([
                    'content' => PostContent::fromPlainText($post->content),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations: turn paragraphs and line breaks back into newlines and drop the markup.
     */
    public function down(): void
    {
        DB::table('posts')->orderBy('id')->chunkById(100, function ($posts) {
            foreach ($posts as $post) {
                $text = preg_replace(['/<br\s*\/?>\n?/i', '/<\/p>\s*<p>/i'], ["\n", "\n\n"], $post->content);

                DB::table('posts')->where('id', $post->id)->update([
                    'content' => html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                ]);
            }
        });
    }
};
