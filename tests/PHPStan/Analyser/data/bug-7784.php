<?php // lint >= 8.0

namespace Bug7784;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

/**
 * @phpstan-template T of array<mixed>|string
 */
interface HelloWorld
{
	/** @phpstan-param T $data */
	public function foo(array|string $data): void;
}

/**
 * @phpstan-implements HelloWorld<array{count: int}>
 */
class Foo implements HelloWorld
{
	public function foo(array|string $data): void
	{
		assertType('array{count: int}', $data);
		assertNativeType('array|string', $data);
	}
}
