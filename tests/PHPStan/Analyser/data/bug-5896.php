<?php declare(strict_types = 1);

namespace Bug5896;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @return array{default?: int}
	 */
	public function load(): array
	{
		return [
		];
	}
}

$helloWorld = new HelloWorld();
$x = $y = $helloWorld->load();
assertType('array{default?: int}', $y);
if ($x !== $y) {
	assertType('array{default?: int}', $y);
}
