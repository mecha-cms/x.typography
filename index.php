<?php

namespace x\typography {
    function content($content) {
        if (!$content) {
            return $content;
        }
        // Skip parsing process if we are in these HTML element(s)
        $parts = (array) \preg_split('/(<!--[\s\S]*?-->|' . \implode('|', (static function ($parts) {
            foreach ($parts as $k => &$v) {
                $v = '<' . \x($k) . '(?:\s[\p{L}\p{N}_:-]+(?:=(?:"[^"]*"|\'[^\']*\'|[^\/>]*))?)*>(?:(?R)|[\s\S])*?<\/' . \x($k) . '>';
            }
            unset($v);
            return $parts;
        })([
            'pre' => 1,
            'code' => 1, // Must come after `pre`
            'kbd' => 1,
            'math' => 1,
            'script' => 1,
            'style' => 1,
            'textarea' => 1
        ])) . '|https?:\/\/\S+)/', $content, -1, \PREG_SPLIT_NO_EMPTY | \PREG_SPLIT_DELIM_CAPTURE);
        $content = "";
        foreach ($parts as $part) {
            if (0 === \strpos($part, 'http://') || 0 === \strpos($part, 'https://')) {
                $content .= $part; // Is an URL, skip!
                continue;
            }
            if ($part && '<' === $part[0] && '>' === \substr($part, -1)) {
                if (false !== \strpos($part, '=')) {
                    $part = \preg_replace_callback('/<([\p{L}\p{N}_:-]+)(\s(?:"[^"]*"|\'[^\']*\'|[^>])*)?>/', static function ($m) {
                        return '<' . $m[1] . \preg_replace_callback('/(\s+)(aria-(?:description|label)|alt|summary|title)=(["\'])(.*?)\3/i', static function ($m) {
                            return $m[1] . $m[2] . '=' . $m[3] . \x\typography\from($m[4], false) . $m[3];
                        }, $m[2] ?? "") . '>';
                    }, $part);
                }
                $content .= $part; // Is a HTML tag or comment, skip!
                continue;
            }
            $content .= \x\typography\from($part, true);
        }
        return "" !== $content ? $content : null;
    }
    function from(string $content, $n) {
        $dash = ['–', '—'];
        $dot = ['…'];
        $quote = ['‘', '’', '“', '”'];
        $x = ['×'];
        if ($n) {
            // Normalize HTML entity
            $content = \strtr($content, [
                '&#34;' => '"',
                '&#39;' => "'",
                '&apos;' => "'",
                '&quot;' => '"',
            ]);
        }
        // Convert dash and dot sequence
        $content = \strtr($content, [
            '--' => $dash[1],
            '---' => $dash[1],
            '...' => $dot[0]
        ]);
        $parts = (array) \preg_split('/(' . \implode('|', [
            // `"asdf <asdf> asdf asdf"`
            '(?<!\\\\)"(?:<(?:"[^"]*"|\'[^\']*\'|[^>])+>|[^\n])*?"',
            // `'asdf <asdf> asdf asdf'`
            '(?<!\\\\)\'(?:<(?:"[^"]*"|\'[^\']*\'|[^>])+>|[^\n])*?\'',
            // `<asdf>`
            '<(?:"[^"]*"|\'[^\']*\'|[^>])+>'
        ]) . ')/', $content, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY);
        $content = "";
        foreach ($parts as $part) {
            if ("'" === $part[0] && "'" === \substr($part, -1)) {
                $content .= $quote[0] . \x\typography\from(\substr($part, 1, -1), true) . $quote[1];
                continue;
            }
            if ('"' === $part[0] && '"' === \substr($part, -1)) {
                $content .= $quote[2] . \x\typography\from(\substr($part, 1, -1), true) . $quote[3];
                continue;
            }
            if ('<' === $part[0] && '>' === \substr($part, -1) && false !== \strpos($part, '=')) {
                $content .= \preg_replace_callback('/(\s+)(aria-(?:description|label)|alt|summary|title)=(["\'])(.*?)\3/i', static function ($m) {
                    return $m[1] . $m[2] . '=' . $m[3] . \x\typography\from($m[4], false) . $m[3];
                }, $part);
                continue;
            }
            // `'asdf` to `‘asdf`, `asdf'` to `asdf’`, `asdf's` to `asdf’s`
            $part = \preg_replace(["/\\B(?<!\\\\)'\\b/", "/\\b'(?!\\\\)\\B/", "/\\b'\\b/"], [$quote[0], $quote[1], $quote[1]], $part);
            // `10x10` to `10×10`
            $part = \preg_replace('/(-?(?:\d*[.,])?\d+)(\s*)x(\s*)(-?(?:\d*[.,])?\d+)/', '$1$2' . $x[0] . '$3$4', $part);
            // `10-20` to `10–20`
            $part = \preg_replace('/(-?(?:\d*[.,])?\d+)(\s*)-(\s*)(-?(?:\d*[.,])?\d+)/', '$1$2' . $dash[0] . '$3$4', $part);
            $content .= \strtr($part, [
                '\\"' => '"',
                '\\\'' => "'"
            ]);
        }
        return $content;
    }
    \Hook::set([
        'page.content',
        'page.description',
        'page.title',
    ], __NAMESPACE__ . "\\content", 2.1);
}

namespace {
    if (\defined("\\TEST") && 'x.typography' === \TEST && \is_file($test = __DIR__ . \D . 'test.php')) {
        require $test;
    }
}