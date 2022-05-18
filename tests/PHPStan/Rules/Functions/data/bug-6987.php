<?php declare(strict_types = 1);

namespace Bug6987;

/**
 * @param 'enabled'|'disabled'|'cancelled' $key
 */
function transformKey(string $key): int {
	switch($key){
		case 'enabled':
			return 1;
		case 'disabled':
			return 2;
		case 'cancelled':
			return 3;
		default:
			return 0;
	}
}

class Foo {
	public function doFoo()
	{
		$availableValues =  [
			'ENABLED' => 123123123,
			'DISABLED' => 555555,
			'CANCELLED' => 11111,
		];

		$map = [];
		foreach($availableValues as $key => $value){
			$map[transformKey(strtolower($key))] = $value;
		}

		return $map;

	}
}
