<?php declare(strict_types = 1);

namespace Bug7000;

class Foo
{
	public function doBar(): void
	{
		/** @var array{require?: array<string, string>, require-dev?: array<string, string>} $composer */
		$composer = array();
		foreach (array('require', 'require-dev') as $linkType) {
			if (isset($composer[$linkType])) {
				foreach ($composer[$linkType] as $x) {}
			}
		}
	}
}
