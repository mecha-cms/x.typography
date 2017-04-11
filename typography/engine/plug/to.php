<?php

To::plug('typography', function($text) {
    return (new Converter\Typography)->run($text);
});