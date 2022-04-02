<?php

namespace Bug6383;

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
			if (isset($option['only_in_country'])
				&& !in_array($country, $option['only_in_country'], true)) {
				unset($options[$key]);

				continue;
			}
		}
	}

}
