<?php declare(strict_types = 1);

// A cache file that is this format but incomplete: a CI cache artifact that was archived or
// restored half way, a disk that filled up under something else, a copy that was interrupted.
// Cutting inside the first section's payload is the shape that would otherwise be read as a
// half-populated cache rather than a damaged one.
$file = __DIR__ . '/tmp/resultCache.php';
$contents = file_get_contents($file);
if ($contents === false) {
	throw new RuntimeException('No result cache at ' . $file);
}

file_put_contents($file, substr($contents, 0, 200));
