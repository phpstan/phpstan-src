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
				assertType("array{value: 'a', checked: false, only_in_country: array{'DE'}}|array{value: 'b', checked: false, only_in_country: array{'BE', 'CH', 'DE', 'DK', 'FR', 'NL', 'SE'}}", $option);
				continue;
			}
		}
	}

}
