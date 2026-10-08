<?php // lint >= 8.1

namespace Bug15432Invalid;

/**
 * @template T of string
 * @param array<T, callable(int): string> $callbacks
 */
function consume(array $callbacks): void
{
}

class Handler
{
	public function handle(string $value): int
	{
		return strlen($value);
	}
}

function invalid(Handler $handler): void
{
	consume([
		'method' => $handler->handle(...),
		'closure' => static fn (int $value): string => (string) $value,
	]);
}
