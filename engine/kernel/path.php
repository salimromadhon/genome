<?php

class Path extends Genome {

    public static function B(string $path, $step = 1, string $s = DS) {
        if ($s === DS || $s === '/') {
            if ($step === 1) {
                return basename($path);
            }
        }
        $path = str_replace([DS, '/'], $s, $path);
        return implode($s, array_slice(explode($s, $path), $step * -1));
    }

    public static function D(string $path, $step = 1, string $s = DS) {
        if ($s === DS || $s === '/') {
            $dir = dirname($path, $step);
            return $dir === '.' ? "" : $dir;
        }
        $path = str_replace([DS, '/'], $s, $path);
        $a = explode($s, $path);
        return implode($s, array_slice($a, 0, count($a) - $step));
    }

    public static function N(string $path, $x = false) {
        return (string) pathinfo($path, $x ? PATHINFO_BASENAME : PATHINFO_FILENAME);
    }

    public static function X(string $path, $fail = false) {
        if (strpos($path, '.') === false) return $fail;
        $x = pathinfo($path, PATHINFO_EXTENSION);
        return $x ? strtolower($x) : $fail;
    }

    public static function F(string $path, string $s = DS) {
        $f = pathinfo($path, PATHINFO_DIRNAME);
        $n = pathinfo($path, PATHINFO_FILENAME);
        if ($n === "") {
            $n = pathinfo($path, PATHINFO_BASENAME);
        }
        return str_replace([DS, '/'], $s, $f === '.' ? $n : $f . DS . $n);
    }

    public static function R(string $path, string $root = ROOT, string $s = DS) {
        $root = str_replace([DS, '/'], $s, $root);
        return str_replace([DS, '/', $root . $s, $root], [$s, $s, "", ""], $path);
    }

}