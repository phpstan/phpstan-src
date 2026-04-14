<?php declare(strict_types = 1);

namespace Bug12490Call;

/**
 * @template T
 */
class Container
{
	/** @var T */
	public $value;

	/**
	 * @param T $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}
}

class Foo
{
	/**
	 * @param Container<string|null> $container
	 */
	public function acceptsNullableString(Container $container): void
	{
	}

	/**
	 * @param Container<int|null> $container
	 */
	public function acceptsNullableInt(Container $container): void
	{
	}

	/**
	 * @param Container<float|null> $container
	 */
	public function acceptsNullableFloat(Container $container): void
	{
	}

	/**
	 * @return Container<string|null>
	 */
	public function createNullableString(): Container
	{
		return new Container(null);
	}

	/**
	 * @return Container<int|null>
	 */
	public function createNullableInt(): Container
	{
		return new Container(null);
	}

	/**
	 * @return Container<float|null>
	 */
	public function createNullableFloat(): Container
	{
		return new Container(null);
	}

	public function test(): void
	{
		$this->acceptsNullableString($this->createNullableString());
		$this->acceptsNullableInt($this->createNullableInt());
		$this->acceptsNullableFloat($this->createNullableFloat());
	}
}
