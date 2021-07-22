<?php declare(strict_types = 1);

class HelloWorld
{
	/**
	 * @param \DateTime|\DateTimeImmutable|int $date
	 */
	public function sayHello($date): void
	{
	}

	/**
	 * @param \DateTimeInterface|int $d
	 */
	public function foo($d): void
	{
		$this->sayHello($d);
	}
}

class HelloWorld2
{
	/**
	 * @param \DateTime|\DateTimeImmutable $date
	 */
	public function sayHello($date): void
	{
	}

	/**
	 * @param \DateTimeInterface $d
	 */
	public function foo($d): void
	{
		$this->sayHello($d);
	}
}

class HelloWorld3
{
	/**
	 * @param array<\DateTime|\DateTimeImmutable>|int $date
	 */
	public function sayHello($date): void
	{
	}

	/**
	 * @param \DateTimeInterface $d
	 */
	public function foo($d): void
	{
		$this->sayHello($d);
	}
}
