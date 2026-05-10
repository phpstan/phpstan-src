<?php declare(strict_types = 1);

namespace Bug9833Arrow;

$nativeReturnsNull = fn (): array => null;

$phpDocOnlyReturnsNull =
	/** @return array<string, int> */
	fn () => null;

$nativeIntReturnsNull = fn (): int => null;
