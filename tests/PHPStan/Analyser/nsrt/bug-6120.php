<?php declare(strict_types = 1); // lint >= 8.0

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
