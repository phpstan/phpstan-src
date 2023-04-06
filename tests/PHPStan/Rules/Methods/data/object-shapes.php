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
