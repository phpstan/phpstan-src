<?php // lint >= 7.4

namespace Bug4525;

use SplObjectStorage;

class HelloWorld
{
	/**
	 * @var SplObjectStorage<\DateTime, \DateTimeImmutable>
	 */
	private SplObjectStorage $map;

	public function sayHello(): void
	{
		$this->map = new SplObjectStorage();
	}

	/** @phpstan-return SplObjectStorage<\DateTime, \DateTimeImmutable> */
	public function getMap(): SplObjectStorage
	{
		return $this->map ??= new SplObjectStorage();
	}
}
