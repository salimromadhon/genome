<?php

class File extends Genome {

    public $path = null;
    public $content = null;

    // Cache!
    private static $inspect = [];
    private static $explore = [];

    const config = [
        'size' => [0, 2097152], // Range of allowed file size(s)
        'extension' => ['txt'] // List of allowed file extension(s)
    ];

    public static $config = self::config;

    public function __construct($path = null) {
        $this->path = is_string($path) ? (file_exists($path) ? realpath($path) : null) : $path;
        $this->content = "";
        parent::__construct();
    }

    public function __toString() {
        return $this->path . "";
    }

    // Print the file content
    public function read($fail = null) {
        if (isset($this->path)) {
            $content = filesize($this->path) > 0 ? file_get_contents($this->path) : "";
            return $content !== false ? $content : $fail;
        }
        return $fail;
    }

    // Write `$data` before save
    protected function _put_(string $data) {
        $this->content = $data;
        return $this;
    }

    // Alias for `put`
    protected function _set_(string $data) {
        return $this->_put_($data);
    }

    // Append `$data` before save
    protected function _append_(string $data) {
        $this->content .= $data;
        return $this;
    }

    // Prepend `$data` before save
    protected function _prepend_(string $data) {
        $this->content = $data . $this->content;
        return $this;
    }

    // Print the file content line by line
    public function get($stop = null, $fail = false, $ch = 1024) {
        $i = 0;
        $out = "";
        if (isset($this->path) && filesize($this->path) > 0 && ($hand = fopen($this->path, 'r'))) {
            while (($chunk = fgets($hand, $ch)) !== false) {
                $out .= $chunk;
                if (
                    // `->get(7)`
                    is_int($stop) && $stop === $i ||
                    // `->get('$')`
                    is_string($stop) && strpos($chunk, $stop) !== false ||
                    // `->get(['$', 7])`
                    is_array($stop) && strpos($chunk, $stop[0]) === $stop[1] ||
                    // `->get(function($chunk, $i, $out) {})`
                    is_callable($stop) && fn($stop, [$chunk, $i, $out], $this, static::class)
                ) break;
                ++$i;
            }
            fclose($hand);
            return rtrim($out);
        }
        return $fail;
    }

    // Import the exported PHP file
    public function import($fail = []) {
        $path = $this->path;
        if (!$path || !is_file($path)) {
            return $fail;
        }
        return include $path;
    }

    // Export value to a PHP file
    public static function export(array $data, $format = '<?php return %{0}%;') {
        $self = new static;
        $self->content = candy($format, z($data));
        return $self;
    }

    // Save the `$data` to …
    public function saveTo(string $path, $consent = null) {
        $this->path = $path;
        $path = To::path($path);
        if (!file_exists($d = dirname($path))) {
            mkdir($d, 0775, true);
        }
        file_put_contents($path, $this->content);
        if (isset($consent)) {
            chmod($path, $consent);
        }
        return $path;
    }

    // Save the `$data` as …
    public function saveAs(string $name, $consent = null) {
        $path = $this->path;
        return $path ? $this->saveTo(dirname($path) . DS . basename($name), $consent) : false;
    }

    // Save the `$data`
    public function save($consent = null) {
        return $this->saveTo($this->path, $consent);
    }

    // Rename the file/folder
    public function renameTo(string $name) {
        $path = $this->path;
        if (isset($path)) {
            $b = basename($path);
            $d = dirname($path) . DS;
            $v = $d . $name;
            if ($name !== $b && !file_exists($v)) {
                rename($path, $v);
            }
            $this->path = $v;
        }
        return [$path, $v];
    }

    // Move the file/folder to … (folder)
    public function moveTo(string $folder = ROOT, $as = null) {
        $path = $this->path;
        $out = [];
        if (isset($path)) {
            $b = basename($path);
            if (is_dir($path)) {
                foreach (self::open($path)->copyTo($folder) as $k => $v) {
                    $out[$k] = $v;
                    unlink($k);
                }
                self::open($path)->delete();
                $this->path = $k = $folder . DS . $b;
                if ($as !== null) {
                    rename($k, $v = $folder . DS . $as);
                    $this->path = $out[$k] = $v;
                }
            } else {
                if (!file_exists($folder) || is_file($folder)) {
                    mkdir($folder, 0775, true);
                }
                if (rename($path, $to = $folder . DS . ($as ?: $b))) {
                    $out = [$path => $to];
                }
                $this->path = $to;
            }
        }
        return $out;
    }

    // Copy the file/folder to … (folder)
    public function copyTo(string $folder = ROOT, string $pattern = '%{name}%.%{i}%.%{extension}%') {
        $i = 1;
        $path = $this->path;
        $out = [];
        if (isset($path)) {
            $b = basename($path);
            // Copy folder
            if (is_dir($path)) {
                foreach (self::explore([$path, 1], true, []) as $k => $v) {
                    $dir = dirname($folder . DS . $b . DS . str_replace($path . DS, "", $k));
                    if (!is_dir($dir)) {
                        mkdir($dir, 0775, true);
                    }
                    $out = extend($out, self::open($k)->copyTo($dir, $pattern), false);
                }
                $this->path = $folder . DS . $b;
                return $out;
            }
            // Copy file
            foreach ((array) $folder as $v) {
                if (!is_dir($v)) {
                    mkdir($v, 0775, true);
                }
                $v .= DS . $b;
                if (!file_exists($v)) {
                    if (copy($path, $v)) {
                        $out[$path] = $v;
                    }
                    $i = 1;
                } else if ($pattern) {
                    $v = dirname($v) . DS . candy($pattern, [
                        'name' => pathinfo($v, PATHINFO_FILENAME),
                        'i' => $i,
                        'extension' => pathinfo($v, PATHINFO_EXTENSION)
                    ]);
                    if (copy($path, $v)) {
                        $out[$path] = $v;
                    }
                    ++$i;
                } else {
                    if (copy($path, $v)) {
                        $out[$path] = $v;
                    }
                }
                $this->path = $v;
            }
        }
        return $out;
    }

    // Delete the file
    public function delete() {
        $path = $this->path;
        $out = [];
        if (isset($path)) {
            if (is_dir($path)) {
                $a = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
                $b = new \RecursiveIteratorIterator($a, \RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($b as $v) {
                    $p = $v->getPathname();
                    $out[$p] = 1;
                    if ($v->isFile()) {
                        unlink($p);
                    } else {
                        rmdir($p);
                    }
                }
                rmdir($path);
            } else {
                unlink($path);
            }
            $out[$path] = 1;
        }
        return $out;
    }

    // Alias for `delete`
    public function reset() {
        return $this->delete();
    }

    // Set file permission
    public function consent($consent) {
        $path = $this->path;
        if (isset($path)) {
            chmod($path, $consent);
        }
        return $path;
    }

    // Inspect file path
    public static function inspect(string $path, $key = null, $fail = false) {
        $id = json_encode(func_get_args());
        if (isset(self::$inspect[$id])) {
            $out = self::$inspect[$id];
            return isset($key) ? Anemon::get($out, $key, $fail) : $out;
        }
        $path = To::path($path);
        $n = Path::N($path);
        $x = Path::X($path);
        $exist = file_exists($path);
        $create = $exist ? filectime($path) : null;
        $update = $exist ? filemtime($path) : null;
        $consent = $exist ? fileperms($path) : null;
        $create_date = $create ? date(DATE_WISE, $create) : null;
        $update_date = $update ? date(DATE_WISE, $update) : null;
        $out = [
            'path' => $path,
            'name' => $n,
            'url' => To::URL($path),
            'extension' => is_file($path) ? $x : null,
            'type' => $exist ? mime_content_type($path) : null,
            'create' => $create_date,
            'update' => $update_date,
            'size' => $exist ? self::size($path) : null,
            'consent' => substr(sprintf('%o', $consent), -4),
            'is' => [
                'exist' => $exist,
                // Hidden file/folder only
                'hidden' => $n === "" || strpos($n, '.') === 0 || strpos($n, '_') === 0,
                'file' => is_file($path),
                'files' => is_dir($path),
                'folder' => is_dir($path) // alias for `is.files`
            ],
            '_create' => $create,
            '_update' => $update,
            '_size' => $exist ? filesize($path) : null,
            '_consent' => $consent
        ];
        self::$inspect[$id] = $out;
        return isset($key) ? Anemon::get($out, $key, $fail) : $out;
    }

    // List all file(s) from a folder
    public static function explore($folder = ROOT, $deep = false, $fail = []) {
        $id = json_encode(func_get_args());
        if (isset(self::$explore[$id])) {
            $out = self::$explore[$id];
            return !empty($out) ? $out : $fail;
        }
        $x = null;
        if (is_array($folder)) {
            $x = $folder[1] ?? null;
            $folder = $folder[0];
        }
        $folder = strtr($folder, '/', DS);
        $out = [];
        if ($deep) {
            if (!is_dir($folder)) {
                return $fail;
            }
            $a = new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS);
            $b = $x === 1 || is_string($x) ? \RecursiveIteratorIterator::LEAVES_ONLY : \RecursiveIteratorIterator::SELF_FIRST;
            $c = new \RecursiveIteratorIterator($a, $b);
            if (is_callable($x)) {
                foreach ($c as $v) {
                    $xx = $v->getExtension();
                    $vv = $v->getPathname();
                    if (call_user_func($x, $vv, $v)) {
                        $out[$vv] = $v->isDir() ? 0 : 1;
                    }
                }
            } else {
                foreach ($c as $v) {
                    $xx = $v->getExtension();
                    $vv = $v->getPathname();
                    if ($v->isDir()) {
                        $out[$vv] = 0;
                    } else if ($x === null || $x === 1 || (is_string($x) && strpos(',' . $x . ',', ',' . $xx . ',') !== false)) {
                        $out[$vv] = 1;
                    }
                }
            }
        } else {
            if ($x === 1 || is_string($x)) {
                if ($x === 1) {
                    $x = '*.*';
                } else {
                    $x = '*.{' . $x . '}';
                }
                $files = is(concat(
                    glob($folder . DS . $x, GLOB_BRACE | GLOB_NOSORT),
                    glob($folder . DS . substr($x, 1), GLOB_BRACE | GLOB_NOSORT)
                ), 'is_file');
            } else if ($x === 0) {
                $files = concat(
                    glob($folder . DS . '*', GLOB_ONLYDIR | GLOB_NOSORT),
                    glob($folder . DS . '.*', GLOB_ONLYDIR | GLOB_NOSORT)
                );
            } else {
                $files = concat(
                    glob($folder . DS . '*', GLOB_NOSORT),
                    glob($folder . DS . '.*', GLOB_NOSORT)
                );
            }
            if (is_callable($x)) {
                foreach ($files as $file) {
                    $b = basename($file);
                    if ($b === '.' || $b === '..') {
                        continue;
                    }
                    if (call_user_func($fn, $file, null)) {
                        $out[$file] = is_file($file) ? 1 : 0;
                    }
                }
            } else {
                foreach ($files as $file) {
                    $b = basename($file);
                    if ($b === '.' || $b === '..') {
                        continue;
                    }
                    $out[$file] = is_file($file) ? 1 : 0;
                }
            }
        }
        self::$explore[$id] = $out;
        return !empty($out) ? $out : $fail;
    }

    // Check if file/folder does exist
    public static function exist($path, $fail = false) {
        if (is_array($path)) {
            foreach ($path as $v) {
                $v = To::path($v);
                if (file_exists($v)) {
                    return $v;
                }
            }
            return $fail;
        }
        $path = To::path($path);
        return file_exists($path) ? $path : $fail;
    }

    // Open a file
    public static function open(...$lot) {
        return new static(...$lot);
    }

    // Upload a file
    public static function push($blob, string $path = ROOT) {
        if (!is_array($blob)) {
            return null; // Invalid blob input
        }
        $path = rtrim(strtr($path, '/', DS), DS);
        if (!empty($blob['error'])) {
            return $blob['error']; // Has error, abort!
        }
        if (file_exists($f = $path . DS . $blob['name'])) {
            return false; // File already exists
        }
        // Destination folder does not exist
        if (!file_exists($path) || !is_dir($path)) {
            mkdir($path, 0775, true); // Create one!
        }
        move_uploaded_file($blob['tmp_name'], $f);
        return $f; // There is no error, the file uploaded with success
    }

    // Download the file
    public static function pull(string $file, $type = null) {
        HTTP::header([
            'Content-Description' => 'File Transfer',
            'Content-Type' => $type ?? 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . basename($file) . '"',
            'Content-Length' => filesize($file),
            'Expires' => 0,
            'Pragma' => 'public'
        ]);
        // Show the browser saving dialog!
        readfile($file);
        exit;
    }

    // Convert file size to …
    public static function size($file, $unit = null, $prec = 2) {
        $size = is_numeric($file) ? $file : filesize($file);
        $size_base = log($size, 1024);
        $x = ['B', 'KB', 'MB', 'GB', 'TB'];
        if (!$u = array_search((string) $unit, $x)) {
            $u = $size > 0 ? floor($size_base) : 0;
        }
        $out = round($size / pow(1024, $u), $prec);
        return $out < 0 ? null : trim($out . ' ' . $x[$u]);
    }

}