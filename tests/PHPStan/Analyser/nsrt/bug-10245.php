<?php declare(strict_types = 1); // lint >= 8.0

namespace NsrtBug10245;

use function PHPStan\Testing\assertType;

/**
 * @throws \Exception
 */
function produceInt(): int
{
	return 1;
}

function testTryCatchInWhileTrue(): void
{
	while (true) {
		try {
			$a = produceInt();
			break;
		} catch (\Throwable) {}
	}

	assertType('int', $a);
}
