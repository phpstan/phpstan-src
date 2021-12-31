<?php declare(strict_types=1);

namespace Bug5650;

use function PHPStan\Testing\assertType;

try {
	assert(false, new \RuntimeException('test'));
} catch (\Throwable $exception) {
	assertType(\RuntimeException::class, $exception::class);
}
