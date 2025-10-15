<?php declare(strict_types = 1);

namespace Bug7000b;

class Foo
{
	public function doBar(): void
	{
		/** @var array{require?: array<string, string>, require-dev?: array<string, string>} $composer */
		$composer = array();
		/** @var 'require'|'require-dev' $foo */
		$foo = '';
		foreach (array('require', 'require-dev') as $linkType) {
			if (array_key_exists($linkType, $composer)) {
				foreach ($composer[$linkType] as $x) {} // should not report error
				foreach ($composer[$foo] as $x) {} // should report error. It can be $linkType = 'require', $foo = 'require-dev'
			}
		}
	}
}
