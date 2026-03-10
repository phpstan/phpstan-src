<?php declare(strict_types = 1);

namespace Bug4860;

use function PHPStan\Testing\assertType;

class Test
{
	public function copy(): static
	{
		assertType('class-string<$this(Bug4860\Test)>', get_class($this));
		return $this->copyTo(get_class($this));
	}

	/**
	 * @template T
	 * @param class-string<T> $targetEntity
	 * @return T
	 */
	public function copyTo(string $targetEntity)
	{
		/** @var T */
		return new $targetEntity();
	}
}
