<?php namespace x;

function typography($content) {
    if (!$content) {
        return $content;
    }
    $convert = static function ($v, $reset = 1) {
        // Single and double quote
        $Q = ['‘', '’', '“', '”'];
        // Dash
        $D = ['–', '—'];
        // Ellipsis
        $E = ['…'];
        // Math
        $M = ['×'];
        if ($reset) {
            $v = \strtr($v, [
                // Normalize HTML entit(y|ies)
                '&#34;' => '"',
                '&#39;' => "'",
                '&apos;' => "'",
                '&quot;' => '"',
                // Ignore escape sequence(s)
                "\\'" => '&#39;',
                "\\-" => '&#45;',
                "\\." => '&#46;',
                "\\\"" => '&#34;',
                "\\\\" => '&#92;',
                "\\`" => '&#96;'
            ]);
        }
        // Convert dash(es) and ellipsis
        $v = \strtr($v, [
            '...' => $E[0],
            '---' => $D[1],
            '--' => $D[0]
        ]);
        // Convert single quote(s)
        $v = \preg_replace_callback('/\B(\')(.*?)\1\B/', static function ($m) use ($Q) {
            return $Q[0] . $m[2] . $Q[1];
        }, $v);
        // Convert double quote(s)
        $v = \preg_replace_callback('/\B(")(.*?)\1\B/', static function ($m) use ($Q) {
            return $Q[2] . $m[2] . $Q[3];
        }, $v);
        // Convert single quote(s) in a word
        $v = \preg_replace(['/\B\'/', '/\'\B/', '/\b\'\b/'], [$Q[0], $Q[1], $Q[1]], $v);
        // `10x10` to `10×10`
        $v = \preg_replace('/(-?(?:\d*[.,])?\d+)(\s*)x(\s*)(-?(?:\d*[.,])?\d+)/', '$1$2' . $M[0] . '$3$4', $v);
        return $v;
    };
    // Skip parsing process if we are in these HTML element(s)
    $tags = [
        'pre' => 1,
        'code' => 1, // Must come after `pre`
        'kbd' => 1,
        'math' => 1,
        'script' => 1,
        'style' => 1,
        'textarea' => 1
    ];
    $parts = \preg_split('/(<!--[\s\S]*?-->|' . \implode('|', (static function ($tags) {
        foreach ($tags as $k => &$v) {
            $v = '<' . $k . '(?:\s[^>]*)?>[\s\S]*?<\/' . $k . '>';
        }
        return $tags;
    })($tags)) . '|<[^>\s]+(?:\s[^>]*)?>)/', $content, -1, \PREG_SPLIT_NO_EMPTY | \PREG_SPLIT_DELIM_CAPTURE);
    $out = "";
    foreach ($parts as $v) {
        if ("" === $v) {
            continue;
        }
        if (0 === \strpos($v, '<!--') && '-->' === \substr($v, -3)) {
            // ~
        } else if ('<' === $v[0] && '>' === \substr($v, -1)) {
            if (false !== \strpos($v, '=')) {
                $v = \preg_replace_callback('/ (aria-(?:description|label)|alt|summary|title)=(["\'])(.*?)\2/', static function ($m) use (&$convert) {
                    return ' ' . $m[1] . '=' . $m[2] . $convert($m[3], 0) . $m[2];
                }, $v);
            }
        } else {
            $v = $convert($v);
        }
        $out .= $v;
    }
    return $out;
}

\Hook::set([
    'page.content',
    'page.description',
    'page.title',
], __NAMESPACE__ . "\\typography", 2.1);

if (\defined("\\TEST") && 'x.typography' === \TEST && \is_file($test = __DIR__ . \D . 'test.php')) {
    require $test;
}