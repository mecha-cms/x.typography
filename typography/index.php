<?php

require __DIR__ . DS . 'engine' . DS . 'plug' . DS . 'to.php';

function fn_converter_typography_replace($content, $lot) {
    return To::typography($content);
}

Hook::set([
    'page.title',
    'page.description',
    'page.content',
    'comment.title',
    'comment.description',
    'comment.content'
], 'fn_converter_typography_replace', 2.1);