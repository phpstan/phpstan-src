<?php declare(strict_types = 1);

namespace Bug15464;

function f(string $file): void {
	$dir = dirname($file);
	assert((@include $file) === 'xyz');
	file_put_contents($file, "<?php return 'abc';");
	assert((@include $file) === 'xyz');
}

function g(string $file): void {
	$dir = dirname($file);
	assert((@include $file) === 'xyz');
	chmod($dir, fileperms($dir) & 0444);
	assert((@include $file) === 'xyz');
}
