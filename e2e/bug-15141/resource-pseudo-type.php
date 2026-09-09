<?php

// The `resource` in the pre-PHP 8 PhpStorm stubs is the pseudo-type. It must be a
// real `resource`, not a class named `resource`, so that narrowing works ...

$content = '';
$finfo = finfo_open(FILEINFO_MIME_TYPE);
if ($finfo === FALSE) {
	throw new \RuntimeException('Cannot create finfo instance.');
}

$type = (string) finfo_buffer($finfo, $content);

$conn = pg_connect('');
if ($conn === false) {
	throw new \RuntimeException('Cannot connect.');
}

// ... and so that a `resource|false` return keeps its `false`, even though the
// stub's own `@return` says just `resource`.
$lob = pg_loopen($conn, 1, 'r');
if ($lob === false) {
	throw new \RuntimeException('Cannot open large object.');
}

// The stub has an untyped `@param $result`, so the pseudo-type is the only thing
// that keeps this parameter checked.
$result = pg_exec($conn, 'SELECT 1');
if ($result === false) {
	throw new \RuntimeException('Query failed.');
}

echo pg_numrows($result);
