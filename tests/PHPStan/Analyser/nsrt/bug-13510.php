<?php

namespace Bug13510;

use function PHPStan\Testing\assertType;

final class Foo
{

	/** @param non-empty-list<int> $arr */
	public function test(array $arr): void
	{
		array_unshift($arr, array_pop($arr));
		assertType('non-empty-list<int>', $arr);
	}

	/** @param non-empty-list<int> $arr */
	public function testTwoLines(array $arr): void
	{
		$popped = array_pop($arr);
		array_unshift($arr, $popped);
		assertType('non-empty-list<int>', $arr);
	}

}
