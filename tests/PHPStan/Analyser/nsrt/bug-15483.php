<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15483;

use function PHPStan\Testing\assertType;

interface Input
{
	public function isInteractive(): bool;
}

function prompt(): ?string
{
	return null;
}

function f(Input $input, mixed $option): void
{
	$title = \is_string($option) && $option !== '' ? $option : null;
	if ($title === null && $input->isInteractive()) {
		$title = prompt();
	}
	if ($title === null) {
		return;
	}
	assertType('bool', $input->isInteractive());
}

function g(Input $input, mixed $option): void
{
	$title = \is_string($option) ? $option : null;
	if ($title === null && $input->isInteractive()) {
		$title = prompt();
	}
	if ($title === null) {
		return;
	}
	assertType('bool', $input->isInteractive());
}

/** @param non-empty-string|null $title */
function h(Input $input, ?string $title): void
{
	if ($title === null && $input->isInteractive()) {
		$title = prompt();
	}
	if ($title === null) {
		return;
	}
	assertType('bool', $input->isInteractive());
}

/** @param int<1, max>|null $count */
function i(Input $input, ?int $count, ?int $other): void
{
	if ($count === null && $input->isInteractive()) {
		$count = $other;
	}
	if ($count === null) {
		return;
	}
	assertType('bool', $input->isInteractive());
}

/** @param non-empty-array<int>|null $items */
function j(Input $input, ?array $items, ?array $other): void
{
	if ($items === null && $input->isInteractive()) {
		$items = $other;
	}
	if ($items === null) {
		return;
	}
	assertType('bool', $input->isInteractive());
}
