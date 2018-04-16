<?php

function fn_markdown_replace($in, $mode = 'text') {
    $x = new ParsedownExtraPlugin;
    foreach (Plugin::state('markdown') as $k => $v) {
        $x->{$k} = $v;
    }
    return $x->{$mode}($in);
}

function fn_markdown($in = "", $lot = [], $that) {
    if ($that->get('type') !== 'Markdown') {
        return $in;
    }
    return fn_markdown_replace($in);
}

function fn_markdown_span($in, $lot = [], $that) {
    if ($that->get('type') !== 'Markdown') {
        return $in;
    }
    // return fn_markdown_replace($in, 'line'); TODO
    return t(fn_markdown_replace($in), '<p>', '</p>');
}

From::_('markdown', function($in, $span = false) {
    return fn_markdown_replace($in, $span ? 'span' : 'text');
});

To::_('markdown', function($in) {
    return $in; // TODO
});

Hook::set('*.title', 'fn_markdown_span', 2);
Hook::set(['*.description', '*.content'], 'fn_markdown', 2);

// Add `markdown` to the allowed file extension(s)
File::$config['extension'] = array_merge(File::$config['extension'], [
    'markdown',
    'md',
    'mkd'
]);