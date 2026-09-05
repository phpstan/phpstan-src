<?php declare(strict_types = 1);

namespace Bug13054;

use function PHPStan\Testing\assertType;

class Test {
	public function fetchTest(int $i): void {
		echo $i;
	}

	public function fetchTest2(): void {
		echo 'test';
	}

	public function exec(): void {
		$list = [
			'test1' => 'Test',
			'test2' => 'Test2',
		];

		foreach ($list as $key => $functionName) {
			$functionToCall = 'fetch' . $functionName;

			if ($key === 'test1') {
				assertType("'fetchTest'", $functionToCall);
				$this->$functionToCall(1);
			} else {
				assertType("'fetchTest2'", $functionToCall);
				$this->$functionToCall();
			}
		}
	}
}
