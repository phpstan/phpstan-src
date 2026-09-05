<?php

namespace Bug15141Php7;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

function doFoo(): void
{
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	if ($finfo === false) {
		return;
	}

	assertType('resource', $finfo);
	assertNativeType('resource', $finfo);
}

function doBar(): void
{
	$connection = pg_connect('');
	if ($connection === false) {
		return;
	}

	$result = pg_exec($connection, 'SELECT 1');
	assertType('resource|false', $result);
	if (is_resource($result)) {
		assertType('resource', $result);
	}

	$lob = pg_loopen($connection, 1, 'r');
	if (is_resource($lob)) {
		assertType('resource', $lob);
	}
}
