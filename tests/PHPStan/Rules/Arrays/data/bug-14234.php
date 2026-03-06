<?php declare(strict_types = 1);

namespace Bug14234;

use function PHPStan\Testing\assertType;

function getShortenedPath(string $identifier): string
{
	$parts = explode('/', $identifier);
	assertType('non-empty-list<string>', $parts);

	for ($i = 0; $i < count($parts) - 1; $i++) {
		$parts[$i] = substr($parts[$i], 0, 1);
	}

	return implode("/", $parts);
}

function getShortenedPath2(string $identifier): string
{
	$parts = explode('/', $identifier);
	assertType('non-empty-list<string>', $parts);

	for ($i = 0; $i < count($parts); $i++) {
		$parts[$i] = substr($parts[$i], 0, 1);
	}

	return implode("/", $parts);
}

function getShortenedPath3(string $identifier): string
{
	$parts = explode('/', $identifier);
	assertType('non-empty-list<string>', $parts);

	for ($i = 0; $i < count($parts) - 4; $i++) {
		$parts[$i] = substr($parts[$i], 0, 1);
	}

	return implode("/", $parts);
}

function getShortenedPath4(array $parts): string
{
	assertType('array', $parts);

	for ($i = 0; $i < count($parts) - 4; $i++) {
		$parts[$i] = substr($parts[$i], 0, 1);
	}

	return implode("/", $parts);
}
