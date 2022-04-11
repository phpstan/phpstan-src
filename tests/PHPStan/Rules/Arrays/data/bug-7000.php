<?php declare(strict_types = 1);

namespace Bug7000;

class Foo
{
	public function doBar(): void
	{
		/** @var array{require?: array<string, string>, require-dev?: array<string, string>} $composer */
		$composer = array();
		/** @var 'require'|'require-dev' $foo */
		$foo = '';
		foreach (array('require', 'require-dev') as $linkType) {
			if (isset($composer[$linkType])) {
				foreach ($composer[$linkType] as $x) {} // should not report error
				foreach ($composer[$foo] as $x) {} // should report error. It can be $linkType = 'require', $foo = 'require-dev'
			}
		}
	}
}
