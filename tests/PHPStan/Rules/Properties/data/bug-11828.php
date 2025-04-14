<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug11828;

class Dummy
{
	/**
	 * @var callable
	 */
	private $callable;
	private readonly int $foo;

	public function __construct(int $foo)
	{
		$this->foo = $foo;

		$this->callable = function () {
			$foo = $this->getFoo();
		};
	}

	public function getFoo(): int
	{
		return $this->foo;
	}

	public function getCallable(): callable
	{
		return $this->callable;
	}
}
