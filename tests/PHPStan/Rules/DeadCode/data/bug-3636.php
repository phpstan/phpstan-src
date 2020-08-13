<?php // lint >= 7.4

namespace Bug3636;

class Foo
{

	/** @var \DateTimeImmutable */
	private $date;

	public function getDate(): \DateTimeImmutable
	{
		return $this->date ??= new \DateTimeImmutable();
	}

}

class Bar
{

	/** @var \DateTimeImmutable */
	private $date;

	public function getDate(): ?\DateTimeImmutable
	{
		return $this->date ?? null;
	}

}

class Baz
{

	/** @var string */
	private $date;

	public function getDate(): string
	{
		return $this->date ?? ($this->date = random_bytes(16));
	}

}

class Lorem
{

	/** @var string */
	private static $date;

	public function getDate(): string
	{
		return self::$date ?? (self::$date = random_bytes(16));
	}

}
