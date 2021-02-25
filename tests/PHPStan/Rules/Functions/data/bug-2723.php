<?php declare(strict_types=1);

namespace Bug2723;

/**
 * @template T1
 */
class Foo
{
	/** @var T1 */
	public $t;

	/** @param T1 $t */
	public function __construct($t)
	{
		$this->t = $t;
	}
}

/**
 * @template T2
 */
class Bar
{
	/** @var T2 */
	public $t;

	/** @param T2 $t */
	public function __construct($t)
	{
		$this->t = $t;
	}
}

/**
 * @template T3
 * @extends Bar<Foo<T3>>
 */
class BarOfFoo extends Bar
{
	/** @param T3 $t */
	public function __construct($t)
	{
		parent::__construct(new Foo($t));
	}
}

/**
 * @template T4
 * @param T4 $t
 * @return Bar<Foo<T4>>
 */
function baz($t)
{
	return new BarOfFoo("hello");
}

/**
 * @template T4
 * @param T4 $t
 * @return Bar<Foo<T4>>
 */
function baz2($t)
{
	return new BarOfFoo($t);
}
