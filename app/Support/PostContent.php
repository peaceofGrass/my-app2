<?php

namespace App\Support;

use App\Models\PostImage;
use DOMDocument;
use DOMElement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Helpers for the HTML a post body is stored as (written in the Tiptap editor).
 */
class PostContent
{
    /**
     * The longest HTML body the sanitizer will accept, in characters.
     */
    public const MAX_LENGTH = 200_000;

    /**
     * The formatting classes the editor's presets produce, per element (styled in resources/css/app.css).
     * Keep in sync with resources/js/editorFormatting.js; any other class is stripped.
     */
    public const FORMAT_CLASSES = [
        'span' => [
            'rt-font-gothic', 'rt-font-serif', 'rt-font-round', 'rt-font-pen', 'rt-font-display',
            'rt-size-sm', 'rt-size-lg', 'rt-size-xl',
            'rt-color-gray', 'rt-color-red', 'rt-color-orange', 'rt-color-yellow',
            'rt-color-green', 'rt-color-blue', 'rt-color-purple', 'rt-color-pink',
        ],
        'mark' => [
            'rt-bg-yellow', 'rt-bg-green', 'rt-bg-blue', 'rt-bg-pink', 'rt-bg-purple',
        ],
    ];

    /**
     * Strip everything the editor cannot produce, so the HTML is safe to render with v-html.
     */
    public static function sanitize(string $html): string
    {
        return static::sanitizer()->sanitize($html);
    }

    /**
     * Whether the body has neither text nor images, e.g. the editor's empty "<p></p>".
     */
    public static function isBlank(string $html): bool
    {
        return static::plainText($html) === '' && ! str_contains($html, '<img');
    }

    /**
     * The ids of the editor images the body references through data-image-id.
     *
     * @return array<int, int>
     */
    public static function imageIds(string $html): array
    {
        if (! str_contains($html, '<img')) {
            return [];
        }

        $ids = [];

        foreach (static::parse($html)->getElementsByTagName('img') as $img) {
            $id = filter_var($img->getAttribute('data-image-id'), FILTER_VALIDATE_INT);

            if ($id !== false && $id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Point every image at its stored file and drop the images that are not in the given set,
     * so the body can only show images that were uploaded by the post's author.
     *
     * @param  Collection<int, PostImage>  $images
     */
    public static function bindImages(string $html, Collection $images): string
    {
        if (! str_contains($html, '<img')) {
            return $html;
        }

        $document = static::parse($html);
        $images = $images->keyBy('id');

        /** @var array<int, DOMElement> $imgs */
        $imgs = iterator_to_array($document->getElementsByTagName('img'));

        foreach ($imgs as $img) {
            $image = $images->get((int) $img->getAttribute('data-image-id'));

            if ($image === null) {
                $img->parentNode?->removeChild($img);

                continue;
            }

            $img->setAttribute('src', $image->url);
        }

        return static::serialize($document);
    }

    /**
     * A plain-text summary of the body for post cards.
     */
    public static function excerpt(string $html, int $limit = 200): string
    {
        return Str::limit(static::plainText($html), $limit);
    }

    /**
     * Convert a plain-text body (written before the editor existed) into paragraphs of HTML.
     */
    public static function fromPlainText(string $text): string
    {
        $paragraphs = preg_split('/\R{2,}/u', trim(str_replace(["\r\n", "\r"], "\n", $text))) ?: [];

        return collect($paragraphs)
            ->filter(fn (string $paragraph) => trim($paragraph) !== '')
            ->map(fn (string $paragraph) => '<p>'.nl2br(e(trim($paragraph)), false).'</p>')
            ->implode('');
    }

    /**
     * The visible text of the body with block boundaries turned into spaces.
     */
    private static function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags(str_replace('<', ' <', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $text) ?? '');
    }

    private static function sanitizer(): HtmlSanitizer
    {
        $config = (new HtmlSanitizerConfig)
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('code')
            ->allowElement('pre')
            ->allowElement('blockquote')
            ->allowElement('ul')
            ->allowElement('ol', ['start'])
            ->allowElement('li')
            ->allowElement('hr')
            ->allowElement('a', ['href'])
            ->allowElement('img', ['src', 'alt', 'data-image-id'])
            // Text formatting is limited to preset classes; inline styles are never kept.
            ->allowElement('span', ['class'])
            ->allowElement('mark', ['class'])
            ->withAttributeSanitizer(new FormatClassSanitizer)
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowMediaSchemes(['http', 'https'])
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->forceAttribute('a', 'target', '_blank')
            ->withMaxInputLength(static::MAX_LENGTH);

        return new HtmlSanitizer($config);
    }

    private static function parse(string $html): DOMDocument
    {
        $document = new DOMDocument;
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );

        return $document;
    }

    private static function serialize(DOMDocument $document): string
    {
        $root = $document->getElementsByTagName('div')->item(0);
        $html = '';

        foreach ($root?->childNodes ?? [] as $node) {
            $html .= $document->saveHTML($node);
        }

        return $html;
    }
}
