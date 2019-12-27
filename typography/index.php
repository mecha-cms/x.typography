<?php namespace _\lot\x;

function typography($content) {
    // Single and double quote [o, c, oo, cc]
    $Q = ['‘', '’', '“', '”'];
    // Dash [n, m]
    $D = ['–', '—'];
    // Ellipsis [h]
    $E = ['…'];
    // Math [x]
    $M = ['×'];
    // Skip parsing process if we are in these HTML element(s)
    $tags = [
        'pre' => 1,
        'code' => 1,
        'kbd' => 1,
        'math' => 1,
        'script' => 1,
        'style' => 1,
        'textarea' => 1
    ];
    $parts = \preg_split('/(<!--[\s\S]*?-->|' . \implode('|', (function($tags) {
        foreach ($tags as $k => &$v) {
            $v = '<' . $k . '(?:\s[^>]*)?>[\s\S]*?<\/' . $k . '>';
        }
        return $tags;
    })($tags)) . ')/', $content, null, \PREG_SPLIT_NO_EMPTY | \PREG_SPLIT_DELIM_CAPTURE);
    $out = "";
    foreach ($parts as $v) {
        if ("" === $v) {
            continue;
        }
        $n = \trim(\explode(' ', \strstr(\substr($v, 1), '>', true), 2)[0], '/');
        if ('<' === $v[0] && '>' === \substr($v, -1) && isset($tags[$n])) {
            // Do nothing!
        } else {
            $v = \strtr($v, [
                // Normalize HTML entit(y|ies)
                '&#34;' => '"',
                '&#39;' => "'",
                '&quot;' => '"',
                '&apos;' => "'",
                // Ignore escape sequence(s)
                "\\\\" => '&#92;',
                "\\\"" => '&#34;',
                "\\'" => '&#39;',
                "\\." => '&#46;',
                "\\-" => '&#45;',
                "\\`" => '&#96;',
                // Convert dash(es) and ellipsis
                '...' => $E[0],
                '---' => $D[1],
                '--' => $D[0]
            ]);
            // Convert single quote(s)
            $v = \preg_replace_callback('/\B(\')(.*?)\1\B/', function($m) use($Q) {
                return $Q[0] . $m[2] . $Q[1];
            }, $v);
            // Convert double quote(s)
            $v = \preg_replace_callback('/\B(")(.*?)\1\B/', function($m) use($Q) {
                return $Q[2] . $m[2] . $Q[3];
            }, $v);
            // Rest of the quote(s) should be a closing quote(s)
            $v = \preg_replace(['/\b\'/', '/\b"/'], [$Q[1], $Q[3]], $v);
            // `10x10` to `10×10`
            $v = \preg_replace('/(-?(?:\d*[.,])?\d+)(\s*)x(\s*)(-?(?:\d*[.,])?\d+)/', '$1$2' . $M[0] . '$3$4', $v);
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
