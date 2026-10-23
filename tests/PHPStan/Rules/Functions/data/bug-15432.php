<?php // lint >= 8.1

namespace Bug15432Rule;

/**
 * @template T of string
 * @param array<T, callable(): void> $callbacks
 */
function consume(array $callbacks): void
{
	foreach ($callbacks as $handler) {
		$handler();
	}
}

class Foo
{

	public function handle(): void
	{
	}

	public function handleInt(int $i): void
	{
	}

}

function (Foo $foo): void {
	consume([
		'method' => $foo->handle(...),
		'closure' => function (): void {},
	]);
	consume([
		'method' => $foo->handleInt(...),
		'closure' => function (): void {},
	]);
	consume([
		'method' => $foo->handle(...),
		'closure' => static function (): void {},
	]);
	consume([
		'method' => $foo->handle(...),
		'closure' => fn () => null,
	]);
	$callable = $foo->handle(...);
	consume([
		'method' => $callable,
		'closure' => static function (): void {},
	]);
};

/**
 * @template T of string
 * @param array<T, array<string, callable(): void>> $callbacks
 */
function consumeNested(array $callbacks): void
{
}

function nested(Foo $foo): void
{
	consumeNested([
		'callbacks' => [
			'method' => $foo->handle(...),
			'closure' => static function (): void {},
		],
	]);
}

/**
 * @template T of string
 * @param array<T, callable> $callbacks
 */
function consumeAny(array $callbacks): void
{
}

function expressionCallables(\Closure $callback): void
{
	consumeAny([
		'method' => $callback(...),
		'closure' => static function (): void {},
	]);
	consumeAny([
		'method' => 'strlen'(...),
		'closure' => static function (): void {},
	]);
}

/**
 * @template T of string
 * @param array<T, callable(int): string> $callbacks
 */
function consumeIntToString(array $callbacks): void
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
	consumeIntToString([
		'method' => $handler->handle(...),
		'closure' => static fn (int $value): string => (string) $value,
	]);
}
