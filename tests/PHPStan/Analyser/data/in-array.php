<?php

namespace InArrayTypeSpecifyingExtension;

class Foo
{

	/**
	 * @param string $s
	 * @param string $r
	 * @param $mixed
	 * @param string[] $strings
	 * @param string[] $moreStrings
	 */
	public function doFoo(
		string $s,
		string $r,
		$mixed,
		array $strings,
		array $moreStrings
	)
	{
		if (!in_array($s, ['foo', 'bar'], true)) {
			return;
		}

		if (!in_array($mixed, $strings, true)) {
			return;
		}

		if (in_array($r, $moreStrings, true)) {
			return;
		}

		$fooOrBarOrBaz = 'foo';
		if (rand(0, 1) === 1) {
			$fooOrBarOrBaz = 'bar';
		} elseif (rand(0, 1) === 1) {
			$fooOrBarOrBaz = 'baz';
		}

		if (in_array($fooOrBarOrBaz, ['bar', 'baz'], true)) {
			return;
		}

		die;
	}

}
