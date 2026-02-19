<?php

namespace Bug13709;

use function PHPStan\Testing\assertType;

function doFOo(string $shortName): void
{
	$pos = strpos($shortName, '\\');
	if ($pos === false) {
		return;
	}

	assertType('non-falsy-string', $shortName);
}


