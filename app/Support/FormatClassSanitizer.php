<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Keeps only the editor's preset formatting classes (PostContent::FORMAT_CLASSES) on span and mark.
 */
class FormatClassSanitizer implements AttributeSanitizerInterface
{
    public function getSupportedElements(): ?array
    {
        return array_keys(PostContent::FORMAT_CLASSES);
    }

    public function getSupportedAttributes(): ?array
    {
        return ['class'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $classes = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $allowed = array_values(array_unique(array_intersect($classes, PostContent::FORMAT_CLASSES[$element] ?? [])));

        return $allowed === [] ? null : implode(' ', $allowed);
    }
}
