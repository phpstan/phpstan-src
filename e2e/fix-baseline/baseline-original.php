<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Variable \$\w+ might not be defined\.$#',
	'identifier' => 'variable.undefined',
	'count' => 2,
	'path' => __DIR__ . '/src/Foo.php',
];
$ignoreErrors[] = [
	'message' => '#^Undefined variable\: \$[abc]$#',
	'identifier' => 'variable.undefined',
	'count' => 5,
	'path' => __DIR__ . '/src/Foo.php',
];
$ignoreErrors[] = [
	'message' => '#^Function FixBaselineE2E\\\\bar\(\) should return string but returns int\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Foo.php',
];
$ignoreErrors[] = [
	'messages' => [
		'#^Undefined variable\: \$d$#',
		'#^Unmatched one$#',
		'#^Unmatched two$#',
	],
	'path' => __DIR__ . '/src/Foo.php',
];
$ignoreErrors[] = [
	'message' => '#^Undefined variable\: \$e$#',
	'identifier' => 'variable.undefined',
	'count' => 1,
	'path' => __DIR__ . '/src/Foo.php',
];
$ignoreErrors[] = [
	'message' => '#^Undefined variable\: \$f$#',
	'identifier' => 'variable.undefined',
	'count' => 1,
	'path' => __DIR__ . '/src/Foo.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
