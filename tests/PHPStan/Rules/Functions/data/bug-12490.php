<?php declare(strict_types = 1);

namespace Bug12490Closure;

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
	public function test(): void
	{
		/** @return Container<string|null> */
		$closure = function (?string $val): Container {
			return new Container($val);
		};

		/** @return Container<int|null> */
		$closure2 = function (?int $val): Container {
			return new Container($val);
		};
	}
}
