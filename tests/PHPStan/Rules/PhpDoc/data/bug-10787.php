<?php

namespace Bug10787;

interface FooInterface {}

class Base
{
	/**
	 * @phpstan-assert FooInterface $this
	 */
	public function assertFoo(): void
	{
		if (!$this instanceof FooInterface) {
			throw new \Exception();
		}
	}

	/**
	 * @phpstan-assert-if-true FooInterface $this
	 */
	public function isFoo(): bool
	{
		return $this instanceof FooInterface;
	}

	/**
	 * @phpstan-assert !FooInterface $this
	 */
	public function assertNotFoo(): void
	{
		if ($this instanceof FooInterface) {
			throw new \Exception();
		}
	}

	/**
	 * @phpstan-assert-if-false FooInterface $this
	 */
	public function isNotFoo(): bool
	{
		return !$this instanceof FooInterface;
	}
}

class Extended extends Base implements FooInterface
{
	/**
	 * @phpstan-assert FooInterface $this
	 */
	public function assertFoo(): void
	{
	}

	/**
	 * @phpstan-assert-if-true FooInterface $this
	 */
	public function isFoo(): bool
	{
		return true;
	}

	/**
	 * @phpstan-assert !FooInterface $this
	 */
	public function assertNotFoo(): void
	{
	}

	/**
	 * @phpstan-assert-if-false FooInterface $this
	 */
	public function isNotFoo(): bool
	{
		return false;
	}
}

interface HasAssert
{
	/**
	 * @phpstan-assert FooInterface $this
	 */
	public function assertFoo(): void;
}

class ImplementsHasAssert implements HasAssert, FooInterface
{
	/**
	 * @phpstan-assert FooInterface $this
	 */
	public function assertFoo(): void
	{
	}
}

class BaseWithPropertyAssert
{
	/** @var int|string */
	public $value;

	/**
	 * @phpstan-assert int $this->value
	 */
	public function assertValueIsInt(): void
	{
	}
}

class ExtendedWithIntProperty extends BaseWithPropertyAssert
{
	/** @var int */
	public $value;

	/**
	 * @phpstan-assert int $this->value
	 */
	public function assertValueIsInt(): void
	{
	}
}

class BaseWithMethodAssert
{
	/**
	 * @return int|string
	 */
	public function getValue(): int|string
	{
		return 1;
	}

	/**
	 * @phpstan-assert-if-true int $this->getValue()
	 */
	public function hasIntValue(): bool
	{
		return is_int($this->getValue());
	}
}

class ExtendedWithIntReturn extends BaseWithMethodAssert
{
	public function getValue(): int
	{
		return 1;
	}

	/**
	 * @phpstan-assert-if-true int $this->getValue()
	 */
	public function hasIntValue(): bool
	{
		return true;
	}
}
