<?php declare(strict_types = 1);

namespace Bug15185;

use function PHPStan\Testing\assertType;

function doFoo(string $connectionString, string $query): void
{
	$connection = pg_connect($connectionString);
	if ($connection === false) {
		return;
	}

	$result = pg_exec($connection, $query);
	assertType('resource|false', $result);
	if (is_resource($result)) {
		assertType('resource', $result);
	}
}

function doBar(): void
{
	$handle = curl_init();
	assertType('(resource|false)', $handle);
	if (is_resource($handle)) {
		assertType('resource', $handle);
	}

	$info = curl_getinfo($handle, CURLINFO_HTTP_CODE);
	assertType('int', $info);
}
