<?php

namespace Bug7918;

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
		}

		return $arr3;
	}
}
