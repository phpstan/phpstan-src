<?php

namespace Bug6383Types;

use function PHPStan\Testing\assertType;

class Foo
{

	function doFoo(string $country): void {
		$options = [
			[
				'value' => 'a',
				'checked' => false,
				'only_in_country' => ['DE'],
			],
			[
				'value' => 'b',
				'checked' => false,
				'only_in_country' => ['BE', 'CH', 'DE', 'DK', 'FR', 'NL', 'SE'],
			],
			[
				'value' => 'c',
				'checked' => false,
			],
		];

		foreach ($options as $key => $option) {
			if (isset($option['only_in_country'])) {
				assertType("array{value: 'a'|'b'|'c', checked: false, only_in_country: array{0: 'BE'|'DE', 1?: 'CH', 2?: 'DE', 3?: 'DK', 4?: 'FR', 5?: 'NL', 6?: 'SE'}}", $option);
				continue;
			}
		}
	}

}
