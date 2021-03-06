<?php namespace fn\shield;

// Generate HTML class(es) based on current page conditional statement(s)
function config($content) {
    $if = \Extend::state('shield', 'if');
    if (strpos($content, '<' . $if[0] . ' ') !== false) {
        return preg_replace_callback('#<' . \x($if[0]) . '(?:\s[^<>]*?)?>#', function($m) use($if) {
            if (
                strpos($m[0], ' class="') !== false ||
                strpos($m[0], ' class ') !== false ||
                substr($m[0], -7) === ' class>'
            ) {
                $a = \HTML::apart($m[0]);
                if (isset($a[2]['class[]'])) {
                    $c = [];
                    foreach (['has', 'is', 'not'] as $key) {
                        foreach (array_filter((array) \Config::get($key, [])) as $k => $v) {
                            $c[] = $key . '-' . $k;
                        }
                    }
                    if ($x = \Config::get('is.error')) {
                        $c[] = 'error-' . $x;
                    }
                    $c = array_unique(\concat($a[2]['class[]'], $c));
                    sort($c);
                    $a[2]['class[]'] = $c;
                }
                return \HTML::unite($a);
            }
            return $m[0];
        }, $content);
    }
    return $content;
}

\Hook::set('shield.yield', __NAMESPACE__ . "\\config", 0);