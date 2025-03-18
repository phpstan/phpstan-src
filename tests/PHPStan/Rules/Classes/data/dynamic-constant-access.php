<?php  // lint >= 8.3

namespace ClassConstantDynamicAccess;

final class Foo
{

	private const BAR = 'FOO';

	/** @var 'FOO'|'BAR'|'BUZ' */
	public $name;

	public function test(string $string, object $obj): void
	{
		$bar = 'FOO';

		echo self::{$foo};
		echo self::{$string};
		echo self::{$obj};
		echo self::{$this->name};
	}

}
