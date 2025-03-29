<?php  // lint >= 8.3

namespace ClassConstantDynamicAccess;

final class Foo
{

	private const BAR = 'BAR';

	/** @var 'FOO'|'BAR'|'BUZ' */
	public $name;

	public function test(string $string, object $obj): void
	{
		$bar = 'FOO';

		echo self::{$bar};
		echo self::{$string};
		echo self::{$obj};
		echo self::{$this->name};
	}

	public function testScope(): void
	{
		$name1 = 'FOO';
		$rand = rand();
		if ($rand === 1) {
			$foo = 1;
			$name = $name1;
		} elseif ($rand === 2) {
			$name = 'BUZ';
		} else {
			$name = 'QUX';
		}

		if ($name === 'FOO') {
			echo self::{$name};
		} elseif ($name === 'BUZ') {
			echo self::{$name};
		} else {
			echo self::{$name};
		}

		echo self::{$name};
	}

}
