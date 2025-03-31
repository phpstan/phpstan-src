<?php // lint >= 8.0

namespace PureConstructor;

final class Foo
{

	private string $prop;

	public static $staticProp = 1;

	/** @phpstan-pure */
	public function __construct(
		public int $test,
		string $prop,
	)
	{
		$this->prop = $prop;
		self::$staticProp++;
	}

}

final class Bar
{

	private string $prop;

	/** @phpstan-impure */
	public function __construct(
		public int $test,
		string $prop,
	)
	{
		$this->prop = $prop;
	}

}

final class AssignOtherThanThis
{
	private int $i = 0;

	/** @phpstan-pure */
	public function __construct(
		self $other,
	)
	{
		$other->i = 1;
	}
}
