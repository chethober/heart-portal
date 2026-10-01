<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Sanitizes HTML fragments built by the report template.
 *
 * Those templates concatenate free text typed on the form (descriptions,
 * findings) into markup that is echoed raw, so a value like "<script>" would
 * run in every viewer's browser. This keeps the formatting tags the templates
 * produce (underline, tooltip spans, tables) and drops everything else.
 */
class ReportHtml
{
    private const ALLOWED_TAGS = [
        'u', 'span', 'strong', 'b', 'i', 'em', 'br', 'sub', 'sup', 'small', 'div', 'p', 'hr',
        'table', 'thead', 'tbody', 'tr', 'td', 'th', 'ul', 'ol', 'li',
    ];

    private const ALLOWED_ATTRIBUTES = ['class', 'style', 'title', 'lang', 'dir', 'colspan', 'rowspan'];

    // Removed together with their content rather than unwrapped.
    private const DROPPED_TAGS = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'noscript', 'svg', 'math'];

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root');
        if ($root === null) {
            return e($html);
        }

        self::sanitizeChildren($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    private static function sanitizeChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                    $node->removeChild($child);
                }

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROPPED_TAGS, true)) {
                $node->removeChild($child);

                continue;
            }

            self::sanitizeChildren($child);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Unknown element: keep its (already sanitized) content only.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                $allowed = in_array($name, self::ALLOWED_ATTRIBUTES, true)
                    && ! ($name === 'style' && preg_match('/url\s*\(|expression\s*\(|@import/i', $attribute->value));

                if (! $allowed) {
                    $child->removeAttribute($attribute->name);
                }
            }
        }
    }
}
