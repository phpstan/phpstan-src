<?php declare(strict_types = 1);

namespace Bug9833Functions;

function nativeArrayReturnsNull(): array
{
	if (rand(0, 1)) {
		return null;
	}
	return [];
}

/** @return array<string, int> */
function phpDocOnlyReturnsNull()
{
	if (rand(0, 1)) {
		return null;
	}
	return [];
}

/** @return array<string, int> */
function nativeArrayReturnsWrongPhpDoc(): array
{
	return ['a' => 'hello'];
}

function nativeIntReturnsNull(): int
{
	return null;
}

$closure = function (): array {
	return null;
};

$closurePhpDocOnly =
	/** @return array<string, int> */
	function () {
		return null;
	};

$arrow = fn (): array => null;

$arrowPhpDocOnly =
	/** @return array<string, int> */
	fn () => null;
