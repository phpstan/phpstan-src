<?php declare(strict_types = 1); // lint >= 7.4

namespace Bug7717;

/**
 * @template V of mixed
 */
interface TestInterface {
	public function aFunction(): static;
}

/**
 * @template-implements TestInterface<int>
 */
final class Implementation implements TestInterface {
	public function aFunction(): static
	{
		return $this;
	}
}
