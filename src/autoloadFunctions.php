<?php declare(strict_types = 1);

namespace PHPStan;

/**
 * @return array<int, callable(string): void>
 */
function autoloadFunctions(): array // phpcs:ignore Squiz.Functions.GlobalFunction.Found
{
	return $GLOBALS['__phpstanAutoloadFunctions'] ?? [];
}
