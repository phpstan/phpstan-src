<?php declare(strict_types = 1);

namespace Bug14234;

function getShortenedPath(string $identifier): string
{
	$parts = explode('/', $identifier);

	for ($i = 0; $i < count($parts) - 1; $i++) {
		$parts[$i] = substr($parts[$i], 0, 1);
	}

	return implode("/", $parts);
}

function getShortenedPath2(string $identifier): string
{
	$parts = explode('/', $identifier);

	for ($i = 0; $i < count($parts); $i++) {
		$parts[$i] = substr($parts[$i], 0, 1);
	}

	return implode("/", $parts);
}
