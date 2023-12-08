<?php

declare(strict_types=1);

namespace Bug4396;

interface TheInterface
{
	/** @return static */
	public static function fromString(string $value);
}

abstract class ClassC implements TheInterface
{
	private string $value;

	final private function __construct(string $value)
	{
		$this->value = $value;
	}

	final public static function fromString(string $value): self
	{
		return new static($value);
	}
}

final class ClassB extends ClassC
{
}

final class ClassA
{
	public function classB(): ClassB
	{
		return ClassB::fromString("any");
	}
}
