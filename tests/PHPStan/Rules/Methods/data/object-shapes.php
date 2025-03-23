<?php

namespace ObjectShapesAcceptance;

use Exception;
use stdClass;

class Foo
{

	public function doFoo(Exception $e): void
	{
		$this->doBar(new stdClass());
		$this->doBar(new Exception());
		$this->doBar($e);
	}

	/**
	 * @param object{foo: int, bar: string} $o
	 */
	public function doBar($o): void
	{

	}

	/**
	 * @param object{foo: string, bar: int} $o
	 * @param object{foo?: int, bar: string} $p
	 * @param object{foo: int, bar: string} $q
	 */
	public function doBaz(
		$o,
		$p,
		$q
	): void
	{
		$this->doBar($o);
		$this->doBar($p);
		$this->doBar($q);

		$this->requireStdClass($o);
		$this->requireStdClass((object) []);
		$this->doBar((object) ['foo' => 1, 'bar' => 'bar']); // OK
		$this->doBar((object) ['foo' => 'foo', 'bar' => 1]); // Error
		$this->acceptsObject($o);
	}

	public function requireStdClass(stdClass $std): void
	{

	}

	public function acceptsObject(object $o): void
	{
		$this->doBar($o);
		$this->doBar(new \stdClass());
	}

}

class Bar
{

	/** @var int */
	public $a;

	/**
	 * @param object{a: int} $o
	 */
	public function doFoo(object $o): void
	{
		$this->requireBar($o);
	}

	public function requireBar(self $bar): void
	{
		$this->doFoo($bar);
		$this->doBar($bar);
	}

	/**
	 * @param object{a: string} $o
	 */
	public function doBar(object $o): void
	{

	}

}

/**
 * @property-write int $c
 */
#[\AllowDynamicProperties]
class Baz
{

	/** @var int */
	protected $a;

	/** @var array{foo: int} */
	public $d;

	public function doFoo(): void
	{
		$this->doBar($this);
		$this->doBaz($this);
		$this->doLorem($this);
		$this->doIpsum($this);
	}

	/**
	 * @param object{a: int} $o
	 */
	public function doBar(object $o): void
	{

	}

	/** @var int */
	public static $b;

	/**
	 * @param object{b: int} $o
	 */
	public function doBaz(object $o): void
	{

	}

	/**
	 * @param object{c: int} $o
	 */
	public function doLorem(object $o): void
	{

	}

	/**
	 * @param object{d: array{foo: string}} $o
	 */
	public function doIpsum(object $o): void
	{

	}

}

class OptionalProperty
{

	/**
	 * @param object{foo?: string} $o
	 */
	public function doFoo(object $o): void
	{
		$this->doBar($o);
		$this->doBaz($o);
	}

	/**
	 * @param object{foo?: int} $o
	 */
	public function doBar(object $o): void
	{

	}

	/**
	 * @param object{foo: int} $o
	 */
	public function doBaz(object $o): void
	{

	}

}

final class FinalClass
{

}

class ClassWithFooIntProperty
{

	/** @var int */
	public $foo;

}

class TestAcceptance
{

	/**
	 * @param object{foo: int} $o
	 * @return void
	 */
	public function doFoo(object $o): void
	{

	}

	public function doBar(
		\Traversable $traversable,
		FinalClass $finalClass,
		ClassWithFooIntProperty $classWithFooIntProperty
	)
	{
		$this->doFoo($traversable);
		$this->doFoo($finalClass);
		$this->doFoo($classWithFooIntProperty);
	}

}
