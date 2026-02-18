<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug6120;

use Generator;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @var Generator<int, string> $gen
	 */
	private ?Generator $gen = null;

	public function setGenerator(Generator $gen): void {
		$this->gen = $gen;
	}

	public function sayHello(): void
	{
		while ($v = $this->gen?->current()) {
			assertType('Generator<int, string, mixed, mixed>', $this->gen);
			echo $v;
			$this->gen->next();
		}
	}
}

final class Clazz
{

	public int $foo = 0;

	public function bar(?Clazz $clazz): void
	{
		$result = $clazz?->foo;
		assertType('int|null', $result);
		if ($result !== null) {
			assertType('Bug6120\Clazz', $clazz);
			assertType('int', $result);
			$clazz->bar(null);
		}
	}

	public function baz(?Clazz $clazz): void
	{
		$result = $clazz?->foo;
		if ($result === null) {
			assertType('Bug6120\Clazz|null', $clazz);
			assertType('null', $result);
		} else {
			assertType('Bug6120\Clazz', $clazz);
			assertType('int', $result);
		}
	}

	public function withNullableProperty(?Clazz $clazz): void
	{
		$result = $clazz?->nullableProperty;
		if ($result !== null) {
			assertType('Bug6120\Clazz', $clazz);
			assertType('string', $result);
		}
	}

	public ?string $nullableProperty = null;

	public function withMethodCall(?Clazz $clazz): void
	{
		$result = $clazz?->getFoo();
		if ($result !== null) {
			assertType('Bug6120\Clazz', $clazz);
			assertType('int', $result);
		}
	}

	public function getFoo(): int
	{
		return $this->foo;
	}

}
