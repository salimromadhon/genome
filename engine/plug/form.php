<?php

foreach (['reset', 'submit'] as $kin) {
    Form::_($kin, function($name = null, $value = null, $text = "", $attr = [], $dent = 0) use($kin) {
        $attr['type'] = $kin;
        return Form::button($name, $value, $text, $attr, $dent);
    });
}

foreach ([
    'hidden' => function($name = null, $value = null, $attr = [], $dent = 0) {
        // Do not cache any request data of hidden form element(s)
        HTTP::delete($name);
        return Form::input($name, 'hidden', $value, null, $attr, $dent);
    },
    'file' => function($name = null, $attr = [], $dent = 0) {
        return Form::input($name, 'file', null, null, $attr, $dent);
    },
    'checkbox' => function($name = null, $value = null, $check = false, $text = "", $attr = [], $dent = 0) {
        $a = ['checked' => $check ? true : null];
        if ($value === true) {
            $value = 'true';
        }
        $text = $text ? '&#x0020;' . HTML::span($text) : "";
        return HTML::dent($dent) . HTML::label(Form::input($name, 'checkbox', $value, null, extend($a, $attr)) . $text);
    },
    'radio' => function($name = null, $options = [], $select = null, $attr = [], $dent = 0) {
        $out = [];
        $select = s($select);
        $id = $attr['id'] ?? null;
        unset($attr['id']);
        foreach ($options as $k => $v) {
            $a = ['disabled' => null];
            if (strpos($k, '.') === 0) {
                $a['disabled'] = true;
                $k = substr($k, 1);
            }
            $k = (string) $k;
            $a['checked'] = $select === $k || $select === '.' . $k ? true : null;
            $v = $v ? '&#x0020;' . HTML::span($v) : "";
            if ($id) {
                $a['id'] = $id . ':' . dechex(crc32($k));
            }
            $out[] = HTML::dent($dent) . HTML::label(Form::input($name, 'radio', $k, null, extend($a, $attr)) . $v);
        }
        return implode(HTML::unite('br', false), $out);
    },
    'range' => function($name = null, $range = [0, 0, 1], $attr = [], $dent = 0) {
        if (is_array($value)) {
            if (!array_key_exists('min', $attr)) {
                $attr['min'] = $range[0];
            }
            if (!array_key_exists('max', $attr)) {
                $attr['max'] = $range[2];
            }
        }
        return Form::input($name, 'range', is_array($range) ? $range[1] : $range, null, $attr, $dent);
    }
] as $k => $v) {
    Form::_($k, $v);
}

foreach (['color', 'date', 'email', 'number', 'password', 'search', 'tel', 'text', 'url'] as $kin) {
    Form::_($kin, function($name = null, $value = null, $placeholder = null, $attr = [], $dent = 0) use($kin) {
        return Form::input($name, $kin, $value, $placeholder, $attr, $dent);
    });
}

// Alias(es)
foreach ([
    'blob' => 'file',
    'check' => 'checkbox',
    'pass' => 'password',
    'toggle' => 'checkbox'
] as $k => $v) {
    Form::_($k, Form::_($v));
}