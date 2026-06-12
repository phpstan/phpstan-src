<?php declare(strict_types = 1);

namespace Bug9833Closure;

$nativeReturnsNull = function (): array {
	return null;
};

$phpDocOnlyReturnsNull =
	/** @return array<string, int> */
	function () {
		return null;
	};

$nativeIntReturnsNull = function (): int {
	return null;
};
