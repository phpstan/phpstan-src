<?php declare(strict_types = 1);

namespace InheritDocMergingVar;

use function PHPStan\Analyser\assertType;

class A {}
class B extends A {}

class One
{
	/** @var A */
	protected $property;

	public function method(): void
	{
		assertType('InheritDocMergingVar\A', $this->property);
	}
}

class Two extends One
{
	/** @var B */
	protected $property;

	public function method(): void
	{
		assertType('InheritDocMergingVar\B', $this->property);
	}
}

class Three extends Two
{
	/** Some comment */
	protected $property;

	public function method(): void
	{
		assertType('InheritDocMergingVar\B', $this->property);
	}
}

class Four extends Three
{
	protected $property;

	public function method(): void
	{
		assertType('InheritDocMergingVar\B', $this->property);
	}
}

class Five extends Four
{

	public function method(): void
	{
		assertType('InheritDocMergingVar\B', $this->property);
	}

}

class Six extends Five
{
	protected $property;

	public function method(): void
	{
		// assertType('InheritDocMergingVar\B', $this->property);
	}
}

class Seven extends One
{

	/**
	 * @inheritDoc
	 * @var B
	 */
	protected $property;

}
