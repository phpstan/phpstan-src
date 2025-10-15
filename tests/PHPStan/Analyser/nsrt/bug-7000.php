<?php

namespace Bug7000Analyser;

use function PHPStan\Testing\assertType;

class Foo
{
	public function doBar(): void
	{
		/** @var array{require?: array<string, string>, require-dev?: array<string, string>} $composer */
		$composer = array();
		foreach (array('require', 'require-dev') as $linkType) {
			if (isset($composer[$linkType])) {
				assertType('array{require?: array<string, string>, require-dev?: array<string, string>}', $composer);
				foreach ($composer[$linkType] as $x) {}
			}
		}
	}
}
