<?php // lint >= 8.1

namespace Bug15432;

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

consume([
	'method' => (new class {
		public function handle(): void {}
	})->handle(...),
	'closure' => function (): void {},
]);

class Handler
{
	public function handle(): void {}
}

function instanceMethods(Handler $handler): void
{
	consume([
		'method' => $handler->handle(...),
		'closure' => static function (): void {},
	]);
	consume([
		'method' => $handler->handle(...),
		'closure' => fn () => null,
	]);
	$callable = $handler->handle(...);
	consume([
		'method' => $callable,
		'closure' => static function (): void {},
	]);
}

/**
 * @template T of string
 * @param array<T, array<string, callable(): void>> $callbacks
 */
function consumeNested(array $callbacks): void
{
}

function nested(Handler $handler): void
{
	consumeNested([
		'callbacks' => [
			'method' => $handler->handle(...),
			'closure' => static function (): void {},
		],
	]);
}
