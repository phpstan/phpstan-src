<?php

namespace Bug7918;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

class TestController
{
	/** @return array<int, string> */
	private function someFunc(): array
	{
		return [];
	}

	private function rand(): bool
	{
		return random_int(0, 1) > 0;
	}

	/**
	 * @phpstan-impure
	 */
	private function randImpure(): bool
	{
		return random_int(0, 1) > 0;
	}

	/** @return list<array<string, bool>> */
	public function run(): array
	{
		$arr3 = [];
		foreach ($this->someFunc() as $id => $arr2) {
			// Solution 1 - Specify $result type
			// /** @var array<string,bool> $result */
			$result = [
				'val1' => false,
				'val2' => false,
				'val3' => false,
				'val4' => false,
				'val5' => false,
				'val6' => false,
				'val7' => false,
			];

			if ($this->rand()) {
				$result['val1'] = true;
			}
			if ($this->rand()) {
				$result['val2'] = true;
			}

			if ($this->rand()) {
				$result['val3'] = true;
			}

			if ($this->rand()) {
				$result['val4'] = true;
			}

			if ($this->rand()) {
				$result['val5'] = true;
			}

			if ($this->rand()) {
				$result['val6'] = true;
			}

			// Solution 2 - reduce cyclomatic complexity by replacing above statements with the following
			// $result['val1'] = $this->rand();
			// $result['val2'] = $this->rand();
			// $result['val3'] = $this->rand();
			// $result['val4'] = $this->rand();
			// $result['val5'] = $this->rand();
			// $result['val6'] = $this->rand();


			$arr3[] = $result;
			assertType('non-empty-array<int, array{val1: bool, val2: bool, val3: bool, val4: bool, val5: bool, val6: bool, val7: false}>', $arr3);
		}

		assertType('array<int, array{val1: bool, val2: bool, val3: bool, val4: bool, val5: bool, val6: bool, val7: false}>', $arr3);

		return $arr3;
	}

	/** @return list<array<string, bool>> */
	public function runImpure(): array
	{
		$arr3 = [];
		foreach ($this->someFunc() as $id => $arr2) {
			// Solution 1 - Specify $result type
			// /** @var array<string,bool> $result */
			$result = [
				'val1' => false,
				'val2' => false,
				'val3' => false,
				'val4' => false,
				'val5' => false,
				'val6' => false,
				'val7' => false,
			];

			if ($this->randImpure()) {
				$result['val1'] = true;
			}
			if ($this->randImpure()) {
				$result['val2'] = true;
			}

			if ($this->randImpure()) {
				$result['val3'] = true;
			}

			if ($this->randImpure()) {
				$result['val4'] = true;
			}

			if ($this->randImpure()) {
				$result['val5'] = true;
			}

			if ($this->randImpure()) {
				$result['val6'] = true;
			}

			$arr3[] = $result;
			assertType('non-empty-array<int, array{val1: bool, val2: bool, val3: bool, val4: bool, val5: bool, val6: bool, val7: false}>', $arr3);
		}

		assertType('array<int, array{val1: bool, val2: bool, val3: bool, val4: bool, val5: bool, val6: bool, val7: false}>', $arr3);

		return $arr3;
	}
}
