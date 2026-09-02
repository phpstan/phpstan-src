<?php declare(strict_types = 1);

// The same shape as truncate.php, but cutting far enough in to land inside one of the sections
// restore() reads lazily. Those are walked rather than decoded when the cache is opened, and
// fseek() past the end of a file succeeds, so only comparing the position with the file size
// catches this - otherwise the section would be handed out as a callback pointing past the end.
$file = __DIR__ . '/tmp/resultCache.php';
$contents = file_get_contents($file);
if ($contents === false) {
	throw new RuntimeException('No result cache at ' . $file);
}

file_put_contents($file, substr($contents, 0, (int) (strlen($contents) * 0.9)));
