<?php

namespace Bug10088;

use PHPStan\TrinaryLogic;
use stdClass;
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
			assertVariableCertainty(TrinaryLogic::createNo(), $shortcut_id);
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

	function testDrupal():void {
		assertVariableCertainty(TrinaryLogic::createNo(), $book_links);
		if (isset($x)) {
			$book_links = 1;
			assertVariableCertainty(TrinaryLogic::createYes(), $book_links);
		}

		assertVariableCertainty(TrinaryLogic::createMaybe(), $book_links);
		if (isset($book_links)) {
			assertVariableCertainty(TrinaryLogic::createYes(), $book_links);
			var_dump($book_links);
		}
		assertVariableCertainty(TrinaryLogic::createMaybe(), $book_links);
	}


}
