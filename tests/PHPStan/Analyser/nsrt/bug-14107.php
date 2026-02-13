<?php declare(strict_types = 1);

namespace Bug14107;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** @param array{0: '1.2'|'a'} $arr */
	public function constantArrayCountValues(array $arr): void
	{
		// '1.2' is a numeric string but not an integer string, so it should stay as string key
		assertType("non-empty-array<'1.2'|'a', int<1, max>>", array_count_values($arr));
	}

	/** @param array{0: '1'|'a'} $arr */
	public function intStringArrayCountValues(array $arr): void
	{
		// '1' is an integer string, so it gets cast to int key
		assertType("non-empty-array<1|'a', int<1, max>>", array_count_values($arr));
	}
}
