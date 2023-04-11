<?php

namespace ObjectShapesAcceptance;

use Exception;
use stdClass;

class Foo
{

	public function doFoo(): void
	{
		$this->doBar(new stdClass());
		$this->doBar(new Exception());
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
