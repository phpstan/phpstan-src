<?php declare(strict_types = 1); // lint >= 7.4

namespace Feature11775;

/** @immutable */
class FooImmutable
{
	private int $i;

	public function __construct(int $i)
	{
		$this->i = $i;
	}

	public function getId(): int
	{
		return $this->i;
	}

	public function setId(): void
	{
		$this->i = 5;
	}
}

/** @readonly */
class FooReadonly
{
	private int $i;

	public function __construct(int $i)
	{
		$this->i = $i;
	}

	public function getId(): int
	{
		return $this->i;
	}

	public function setId(): void
	{
		$this->i = 5;
	}
}
