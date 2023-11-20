<?php

namespace Bug10088;

use PHPStan\TrinaryLogic;
use stdClass;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	function doFoo(): void {
		if (rand(0,1)) {
			$shortcut_id = 1;
			assertVariableCertainty(TrinaryLogic::createYes(), $shortcut_id);
		}

		assertVariableCertainty(TrinaryLogic::createMaybe(), $shortcut_id);

		$link_mode = isset($shortcut_id) ? "remove" : "add";
		if ($link_mode === "add") {
			assertVariableCertainty(
				// should be NO, see https://github.com/phpstan/phpstan-src/pull/2710#issuecomment-1793677703
				TrinaryLogic::createMaybe(),
				$shortcut_id
			);
		} else {
			assertVariableCertainty(TrinaryLogic::createYes(), $shortcut_id);
		}

		assertVariableCertainty(TrinaryLogic::createMaybe(), $shortcut_id);
	}

	/**
	 * @param mixed[] $period
	 */
	public function testCarbon(array $period): void
	{
		foreach ($period as $date) {
			break;
		}

		assertVariableCertainty(TrinaryLogic::createMaybe(), $date);
		$this->assertInstanceOfStdClass($date ?? null);
		assertVariableCertainty(TrinaryLogic::createYes(), $date);
	}

	/**
	 * @param mixed $m
	 * @phpstan-assert stdClass $m
	 */
	private function assertInstanceOfStdClass($m): void
	{
		if (!$m instanceof stdClass) {
			throw new \Exception();
		}
	}

	/**
	 * @param mixed[] $period
	 */
	public function testCarbon2(array $period): void
	{
		foreach ($period as $date) {
			break;
		}

		assertVariableCertainty(TrinaryLogic::createMaybe(), $date);
		assert(($date ?? null) instanceof stdClass);
		assertVariableCertainty(TrinaryLogic::createYes(), $date);
	}

	function constantIfElse(int $x): void {
		$link_mode = $x > 10 ? "remove" : "add";
		if ($link_mode === "add") {
			assertType('int<min, 10>', $x);
		} else {
			assertType('int<11, max>', $x);
		}
	}

	function constantIfElseShort(int $x): void {
		$link_mode = $x > 10 ?: "remove";
		if ($link_mode === "remove") {
			assertType('int<min, 10>', $x);
		} else {
			assertType('int<11, max>', $x);
		}
	}

	/**
	 * @param string[] $arr
	 * @param 0|positive-int $posInt
	 */
	function overlappingIfElseType($arr, int $x, int $posInt): void {
		$link_mode = $arr ? $posInt : $x;
		assert($link_mode >= 0);

		assertType('array<string>', $arr);
	}

}
