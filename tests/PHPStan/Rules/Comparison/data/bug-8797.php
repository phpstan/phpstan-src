<?php declare(strict_types = 1);

namespace Bug8797;

final class TestClass
{
	public function testMethod(float $float): void {
		$boolean = filter_var($float, FILTER_VALIDATE_INT) !== false;
		if (!$boolean) {
			return;
		}
	}
}
