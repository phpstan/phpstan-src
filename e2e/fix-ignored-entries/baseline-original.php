<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Call modern\(\) instead\.$#',
	'identifier' => 'fixIgnoredEntriesE2E.rename',
	'count' => 2,
	'path' => __DIR__ . '/src/InPhpBaseline.php',
];
// stale before --fix-ignored, in a file it does not change, so it is left alone
$ignoreErrors[] = [
	'message' => '#^Stale$#',
	'path' => __DIR__ . '/src/Untouched.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
