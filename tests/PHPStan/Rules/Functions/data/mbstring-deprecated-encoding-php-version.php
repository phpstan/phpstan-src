<?php

namespace MbstringDeprecatedEncodingPhpVersion;

function doFoo(string $s): void
{
	if (PHP_VERSION_ID < 80200) {
		mb_strlen($s, 'BASE64');
	}

	if (PHP_VERSION_ID >= 80200) {
		mb_strlen($s, 'BASE64');
	}
}
