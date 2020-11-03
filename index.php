<?php
    $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

    $template = file_get_contents('src/main.html');
    $js = str_replace('{%URL%}', $url, file_get_contents('src/main.js'));
    $out = str_replace([
        '{%STYLE%}',
        '{%JS%}'
    ], [
        file_get_contents('src/style.css'),
        $js,
    ], $template);

    exit($out);