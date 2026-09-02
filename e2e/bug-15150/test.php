<?php declare(strict_types = 1);

namespace Bug15150;

/**
 * @param array<int, string> $a
 */
function doFoo(array $a): void
{
	// PHP 8.5 functions, polyfilled by symfony/polyfill-php85 bundled with PHPStan
	echo array_first($a);
	echo array_last($a);

	// PHP 8.4 function, available in the analysed PHP version
	echo array_find($a, static fn (string $v): bool => $v !== '') ?? '';
}

function doBar(): void
{
	// PHP 8.5 class, polyfilled by symfony/polyfill-php85 bundled with PHPStan
	throw new \Filter\FilterException('foo');
}
