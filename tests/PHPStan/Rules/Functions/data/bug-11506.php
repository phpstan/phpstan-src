<?php

namespace Bug11506;

function (): void {
	$brandName         = 'Mercedes-Benz';
	$enabledChars      = './-';
	$enabledCharsCount = \strlen($enabledChars);

	for ($i = 0; $i < $enabledCharsCount; $i++) {
		$parts = \explode($enabledChars[$i], $brandName);
	}
};
