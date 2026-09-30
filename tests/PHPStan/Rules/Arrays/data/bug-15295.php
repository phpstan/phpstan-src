<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15295;

/** @param array{heading: string, start: float} $section */
function resetStart(array $section): array
{
	return [...$section, 'start' => 0.0];
}

function resetStartOfLocalVariable(): array
{
	$section = ['heading' => 'h', 'start' => 1.0];
	return [...$section, 'start' => 0.0];
}

/** @param array{heading: string, start: float} $section */
function defaultsOverriddenBySpread(array $section): array
{
	return ['start' => 0.0, ...$section];
}

/**
 * @param array{heading: string, start: float} $a
 * @param array{start: float} $b
 */
function twoSpreads(array $a, array $b): array
{
	return [...$a, ...$b];
}

/** @return array{heading: string, start: float} */
function getSection(): array
{
	return ['heading' => 'h', 'start' => 1.0];
}

function resetStartOfCall(): array
{
	return [...getSection(), 'start' => 0.0];
}

function spreadOfLiteral(): array
{
	return [...['heading' => 'h', 'start' => 1.0], 'start' => 0.0];
}

/** @param array{heading: string, start: float} $section */
function duplicateExplicitKeysAfterSpread(array $section): array
{
	return [...$section, 'start' => 0.0, 'start' => 1.0];
}

function intKeysOfVariable(): array
{
	$list = [1, 2];
	return [...$list, 0 => 'x'];
}
