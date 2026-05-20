<?php

namespace GeneralizeScopeRecursiveType;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(array $array, array $values)
	{
		$data = [];
		foreach ($array as $val) {
			foreach ($values as $val2) {
				$data['foo'] = array_merge($data, $this->doBar());
			}
		}

		assertType('array{}|array{foo: array<array<int|string>>}', $data);
	}

	/**
	 * @return string[][]|int[][]
	 */
	private function doBar(): array
	{
		return [];
	}

}
