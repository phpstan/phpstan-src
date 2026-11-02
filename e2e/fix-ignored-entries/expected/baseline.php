<?php declare(strict_types = 1);

$ignoreErrors = [];
// stale before --fix-ignored, in a file it does not change, so it is left alone
$ignoreErrors[] = [
	'message' => '#^Stale$#',
	'path' => __DIR__ . '/src/Untouched.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
