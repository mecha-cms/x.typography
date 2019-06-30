<?php namespace _\page;

function typography($content) {
    return (new \To\Typography)->apply($content);
}

\Hook::set([
    '*.content',
    '*.description',
    '*.title',
], __NAMESPACE__ . "\\typography", 2.1);