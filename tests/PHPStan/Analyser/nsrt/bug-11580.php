<?php

namespace Bug11580;

use function PHPStan\Testing\assertType;

final class HelloWorld
{
	public function bad(string $in): void
	{
		$matches = [];
		if (preg_match('~^/xxx/([\w\-]+)/?([\w\-]+)?/?$~', $in, $matches)) {
			assertType('array{0: non-falsy-string, 1: non-empty-string, 2?: non-empty-string}', $matches);
		}
	}

	public function bad2(string $in): void
	{
		$matches = [];
		$result = preg_match('~^/xxx/([\w\-]+)/?([\w\-]+)?/?$~', $in, $matches);
		if ($result) {
			assertType('array{0: non-falsy-string, 1: non-empty-string, 2?: non-empty-string}', $matches);
		}
	}

	public function bad3(string $in): void
	{
		$result = preg_match('~^/xxx/([\w\-]+)/?([\w\-]+)?/?$~', $in, $matches);
		assertType('array{0?: string, 1?: non-empty-string, 2?: non-empty-string}', $matches);
		if ($result) {
			assertType('array{0: non-falsy-string, 1: non-empty-string, 2?: non-empty-string}', $matches);
		}
	}

}
