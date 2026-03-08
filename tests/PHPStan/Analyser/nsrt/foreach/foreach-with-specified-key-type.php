<?php

namespace ForeachWithGenericsPhpDoc;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array<string, string|int|float> $list
	 */
	public function doFoo(array $list)
	{
		foreach ($list as $key => $value) {
			assertType('non-empty-array<string, float|int|string>', $list);
			assertType('string', $key);
			assertType('float|int|string', $value);
		}
	}

}
