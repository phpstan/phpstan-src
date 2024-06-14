<?php

namespace ForLoopIType;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doBar() {
		$foo = null;
		for($i = 1; $i < 50; $i++) {
			$foo = new \stdClass();
			assertType('int<1, 49>', $i);
		}

		assertType('int<50, max>', $i);
		assertType(\stdClass::class, $foo);

		for($i = 50; $i > 0; $i--) {
			assertType('int<1, 50>', $i);
		}

		assertType('int<min, 0>', $i);
	}

	public function doCount(array $a) {
		$foo = null;
		for($i = 1; $i < count($a); $i++) {
			$foo = new \stdClass();
			assertType('int<1, max>', $i);
		}

		assertType('int<1, max>', $i);
		assertType(\stdClass::class . '|null', $foo);
	}

	public function doCount2() {
		$foo = null;
		for($i = 1; $i < count([]); $i++) {
			$foo = new \stdClass();
			assertType('*NEVER*', $i);
		}

		assertType('1', $i);
		assertType('null', $foo);
	}

	public function doBaz() {
		for($i = 1; $i < 50; $i += 2) {
			assertType('1|int<3, 49>', $i);
		}

		assertType('int<50, max>', $i);
	}

	public function doLOrem() {
		for($i = 1; $i < 50; $i++) {
			break;
		}

		assertType('int<1, max>', $i);
	}

}

interface Foo2 {
	function equals(self $other): bool;
}

class HelloWorld
{
	/**
	 * @param Foo2[] $startTimes
	 * @return mixed[]
	 */
	public static function groupCapacities(array $startTimes): array
	{
		if ($startTimes === []) {
			return [];
		}
		sort($startTimes);

		$capacities = [];
		$current = $startTimes[0];
		$count = 0;
		foreach ($startTimes as $startTime) {
			if (!$startTime->equals($current)) {
				$count = 0;
			}
			$count++;
		}
		assertType('int<1, max>', $count);

		return $capacities;
	}
}
