<?php declare(strict_types = 1);

namespace Bug13270a;

use function PHPStan\Testing\assertType;

final class HelloWorld
{
	/**
	 * @param array<mixed> $data
	 */
	public function test(array $data): void
	{
		foreach($data as $k => $v) {
			assertType('non-empty-array<mixed>', $data);
			$data[$k]['a'] = true;
			assertType("non-empty-array<(non-empty-array&hasOffsetValue('a', true))|(ArrayAccess&hasOffsetValue('a', true))>", $data);
			foreach($data[$k] as $val) {
			}
		}
	}

	/*
	public function doFoo(mixed $mixed, int $i): void
	{
		$mixed[$i]['a'] = true;
		dumpType($mixed);
	}
	*/
}
