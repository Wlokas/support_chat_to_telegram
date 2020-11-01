<?php
    $template = file_get_contents('/src/main.html');
    $out = str_replace([
        '{%STYLE%}',
        '{%JS%}'
    ], [
        file_get_contents('/src/style.css'),
        file_get_contents('/src/main.js'),
    ], $template);

    exit($out);