<?php

declare(strict_types=1);

namespace Witals\Framework\Context;

class BlockMarkupParser
{
    public function parse(string $html): array
    {
        if (str_contains($html, '<!-- wp:')) {
            return $this->parseBlocks($html);
        }

        return [['blockId' => '@core/html', 'attributes' => ['content' => $html], 'children' => []]];
    }

    public function parseBlocks(string $html): array
    {
        $results = [];
        $cursor = 0;
        $length = strlen($html);

        while ($cursor < $length) {
            $open = strpos($html, '<!--', $cursor);
            if ($open === false) {
                $text = substr($html, $cursor);
                if (trim($text) !== '') {
                    $results[] = $this->textNode($text);
                }
                break;
            }

            if ($open > $cursor) {
                $text = substr($html, $cursor, $open - $cursor);
                if (trim($text) !== '') {
                    $results[] = $this->textNode($text);
                }
            }

            $close = strpos($html, '-->', $open);
            if ($close === false) {
                $results[] = $this->textNode(substr($html, $open));
                break;
            }

            $comment = trim(substr($html, $open + 4, $close - $open - 4));
            $cursor = $close + 3;

            if (str_starts_with($comment, '/wp:')) {
                continue;
            }

            if (str_starts_with($comment, 'wp:')) {
                $parsed = $this->parseComment($comment);
                $block = [
                    'blockId' => $parsed['name'],
                    'attributes' => $parsed['attrs'],
                    'children' => [],
                    'html' => '',
                ];

                if ($parsed['void']) {
                    $results[] = $block;
                } else {
                    $contentEnd = $this->findClosingTag($html, $cursor, $parsed['name']);
                    if ($contentEnd === null) {
                        $cursor = $length;
                        $results[] = $block;
                    } else {
                        $innerHtml = substr($html, $cursor, $contentEnd - $cursor);
                        $cursor = $contentEnd + strlen('<!-- /wp:' . $parsed['name'] . ' -->');

                        $innerBlocks = $this->parseBlocks($innerHtml);
                        $innerText = $this->extractInnerText($innerHtml);

                        $block['children'] = $innerBlocks;
                        $block['html'] = trim($innerText);
                        $results[] = $block;
                    }
                }
                continue;
            }

            $results[] = $this->textNode($comment);
        }

        return $results;
    }

    protected function parseComment(string $comment): array
    {
        $body = trim(substr($comment, 3));
        $void = str_ends_with($body, '/');
        $clean = $void ? rtrim($body, ' /') : $body;

        $space = strpos($clean, ' ');
        if ($space !== false) {
            $name = substr($clean, 0, $space);
            $json = substr($clean, $space + 1);
            $attrs = json_decode($json, true) ?? [];
        } else {
            $name = $clean;
            $attrs = [];
        }

        if (strpos($name, '/') === false) {
            $name = 'core/' . $name;
        }

        return ['name' => $name, 'attrs' => $attrs, 'void' => $void];
    }

    protected function findClosingTag(string $html, int $cursor, string $name): ?int
    {
        $depth = 0;
        $search = $cursor;
        $length = strlen($html);

        $closeTarget = '/wp:' . $this->shortName($name);

        while ($search < $length) {
            $nextOpen = strpos($html, '<!--', $search);
            if ($nextOpen === false) {
                return null;
            }

            $nextClose = strpos($html, '-->', $nextOpen);
            if ($nextClose === false) {
                return null;
            }

            $comment = trim(substr($html, $nextOpen + 4, $nextClose - $nextOpen - 4));

            if ($comment === $closeTarget) {
                if ($depth === 0) {
                    return $nextOpen;
                }
                $depth--;
                $search = $nextClose + 3;
                continue;
            }

            if (str_starts_with($comment, '/wp:')) {
                $depth--;
            } elseif (str_starts_with($comment, 'wp:')) {
                $parsed = $this->parseComment($comment);
                if (!$parsed['void']) {
                    $depth++;
                }
            }

            $search = $nextClose + 3;
        }

        return null;
    }

    protected function extractInnerText(string $html): string
    {
        $result = '';
        $cursor = 0;
        $length = strlen($html);

        while ($cursor < $length) {
            $open = strpos($html, '<!--', $cursor);
            if ($open === false) {
                $result .= substr($html, $cursor);
                break;
            }

            if ($open > $cursor) {
                $result .= substr($html, $cursor, $open - $cursor);
            }

            $close = strpos($html, '-->', $open);
            if ($close === false) {
                $result .= substr($html, $open);
                break;
            }

            $cursor = $close + 3;
        }

        return $result;
    }

    protected function textNode(string $text): array
    {
        return [
            'blockId' => '@core/html',
            'attributes' => ['content' => $text],
            'children' => [],
        ];
    }

    public function serialize(array $blocks): string
    {
        $output = '';

        foreach ($blocks as $block) {
            $blockId = $block['blockId'] ?? '';
            $attrs = $block['attributes'] ?? [];
            $children = $block['children'] ?? [];

            if ($blockId === '@core/html') {
                $output .= $attrs['content'] ?? '';
                continue;
            }

            $shortName = $this->shortName($blockId);
            $attrsJson = !empty($attrs) ? ' ' . json_encode($attrs, JSON_UNESCAPED_SLASHES) : '';

            if (empty($children)) {
                $output .= '<!-- wp:' . $shortName . $attrsJson . ' /-->' . "\n";
            } else {
                $output .= '<!-- wp:' . $shortName . $attrsJson . ' -->' . "\n";
                $output .= $this->serialize($children);
                $output .= '<!-- /wp:' . $shortName . ' -->' . "\n";
            }
        }

        return $output;
    }

    protected function shortName(string $blockId): string
    {
        if (str_starts_with($blockId, 'core/')) {
            return substr($blockId, 5);
        }
        return $blockId;
    }
}
