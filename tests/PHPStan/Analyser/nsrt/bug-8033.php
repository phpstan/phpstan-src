<?php declare(strict_types = 1);

namespace Bug8033;

use function PHPStan\Testing\assertType;

function test(string $fileName): string
{
	$pathinfo = pathinfo($fileName);
	assertType('array{dirname?: string, basename: string, extension?: string, filename: string}', $pathinfo);

	if (strlen($fileName) > 0) {
		assertType('array{dirname: string, basename: string, extension?: string, filename: string}', pathinfo($fileName));
	}

	return $pathinfo['dirname'] ?? '';
}
