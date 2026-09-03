<?php declare(strict_types = 1);

// A cache written by a PHPStan whose classes have changed since cannot be reconstructed: the payload
// does not carry the property the class now declares, so the object comes back with it uninitialized
// and reading it throws. Renaming one inside the cache file is that shape without needing two
// PHPStan versions installed, and keeping the byte length identical leaves the rest of the payload
// valid - the frame headers count bytes.
$file = __DIR__ . '/tmp/resultCache.php';
$contents = file_get_contents($file);
if ($contents === false) {
	throw new RuntimeException('No result cache at ' . $file);
}

// Error::$message, read by transformPaths() while the cached errors are absolutized.
$property = "\0PHPStan\\Analyser\\Error\0message";
if (substr_count($contents, $property) !== 1) {
	throw new RuntimeException('Expected exactly one cached Error carrying that property.');
}

$renamed = substr($property, 0, -strlen('message')) . 'messagf';
if (strlen($renamed) !== strlen($property)) {
	throw new RuntimeException('The replacement has to keep the byte length.');
}

file_put_contents($file, str_replace($property, $renamed, $contents));
