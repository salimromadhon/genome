<?php

class Cookie extends __ {

    public static function set($id, $lot = "", $c = []) {
        $cc = [
            'expire' => 1,
            'path' => '/',
            'domain' => "",
            'secure' => false,
            'http_only' => false
        ];
        Anemon::extend($cc, $c);
        $cc = array_values($cc);
        $cc[0] = time() + 60 * 60 * 24 * $cc[0]; // 1 day
        if (strpos($id, '.') !== false) {
            $a = explode('.', $id);
            $n = array_shift($a);
            $o = e($_COOKIE[$n] ?? []);
            if (Is::anemon($lot)) $lot = a($lot);
            Anemon::extend($o, implode('.', $a), $lot);
            $lot = $o;
        }
        array_unshift($cc, [$id, To::base64_x(To::json($lot))]);
        call_user_func_array('setcookie', $cc);
        $cc[0] = '__' . $cc[0];
        $cc[1] = [$cc[2], $cc[3], $cc[4], $cc[5], $cc[6]];
        $cc[3] = '/';
        $cc[4] = "";
        $cc[5] = $cc[6] = false;
        call_user_func_array('setcookie', $cc);
    }

    public static function get($id = null, $fail = "") {
        if ($id === null) return $_COOKIE ?? $fail;
        $c = isset($_COOKIE) ? e($_COOKIE) : $fail;
        $v = Anemon::get($c, $id, $fail);
        return !is_array($v) && To::anemon(To::base64_v($v)) !== null ? To::anemon(To::base64_v($v)) : $v;
    }

    public static function reset($id = null) {
        if ($id === null) {
            $_COOKIE = [];
            foreach (explode(';', $_SERVER['HTTP_COOKIE'] ?? "") as $v) {
                $c = explode('=', $v, 2);
                $n = trim($c[0]);
                setcookie($n, null, -1);
                setcookie($n, null, -1, '/');
            }
        } else {
            if (strpos($id, '.') !== false) {
                $a = explode('.', $id);
                if(isset($_COOKIE[$a[0]])) {
                    $o = e($_COOKIE[$a[0]]);
                    Anemon::reset($o, $id);
                    foreach($o as $k => $v) {
                        $_COOKIE[$a[0]][$k] = is_array($v) ? To::base64_x(To::json($v)) : $v;
                    }
                    $cc = e($_COOKIE['__' . $a[0]]);
                    array_unshift($cc, [$a[0], To::base64_x(To::json($o))]);
                    call_user_func_array('setcookie', $cc);
                }
            } else {
                unset($_COOKIE[$id]);
                setcookie($id, null, -1);
                setcookie($id, null, -1, '/');
            }
        }
    }

}