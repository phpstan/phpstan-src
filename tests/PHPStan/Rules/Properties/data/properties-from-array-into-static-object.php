<?php declare(strict_types = 1);

namespace PropertiesFromArrayIntoStaticObject;

class Foo
{
	/**
	 * @var string
	 */
	public static $foo = '';

	/**
	 * @var null|\stdClass
	 */
	public static $lall;

	/**
	 * @phpstan-return array{foo: 'bar', lall: string, noop: int}
	 */
	public function data(): array
	{
		return ['foo' => 'bar', 'lall' => 'lall', 'noop' => 1];
	}

	public function create(): self {
		$self = new self();

		foreach($this->data() as $property => $value) {
			self::${$property} = $value;

			if ($property === 'lall') {
				self::${$property} = null;
			}

			if ($property === 'foo') {
				self::${$property} = 1.1;
			}
		}

		return $self;
	}
}

class FooBar
{
	/**
	 * @var string
	 */
	public static $foo = '';

	/**
	 * @var null|\stdClass
	 */
	public static $lall;

	public function data(): array
	{
		return ['foo' => 'bar', 'lall' => 'lall', 'noop' => 1];
	}

	public function create(): self {
		$self = new self();

		foreach($this->data() as $property => $value) {
			self::${$property} = $value;

			if ($property === 'lall') {
				self::${$property} = null;
			}

			if ($property === 'foo') {
				self::${$property} = 1.1;
			}
		}

		return $self;
	}
}