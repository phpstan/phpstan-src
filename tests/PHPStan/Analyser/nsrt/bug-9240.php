<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug9240;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-type PhpFileArray array{error: int, name: string}
 */
class Upload
{
	/**
	 * @param \Closure(PhpFileArray, PhpFileArray, PhpFileArray): bool $fx
	 */
	public function onUpload(\Closure $fx): bool
	{
		$v = ['error' => 1, 'name' => 'x'];
		$postFiles = [$v, $v, $v];

		return $fx(...$postFiles);
	}
}

function test(): void
{
	$u = new Upload();
	$u->onUpload(function (...$postFiles): bool {
		assertType('array<int|string, array{error: int, name: string}>', $postFiles);
		foreach ($postFiles as $postFile) {
			assertType('array{error: int, name: string}', $postFile);
			if ($postFile['error'] !== 0) {
				return false;
			}
		}

		return true;
	});
}

/**
 * @param \Closure(int, string, float): void $fx
 */
function mixedTypes(\Closure $fx): void
{
	$fx(1, 'hello', 3.14);
}

function testMixedTypes(): void
{
	mixedTypes(function (...$args): void {
		assertType('array<int|string, float|int|string>', $args);
	});
}

/**
 * @param \Closure(int, string): void $fx
 */
function twoParams(\Closure $fx): void
{
	$fx(1, 'hello');
}

function testVariadicNotFirst(): void
{
	twoParams(function (int $first, string ...$rest): void {
		assertType('int', $first);
		assertType('array<int|string, string>', $rest);
	});
}

// Arrow function version
function testArrowFunction(): void
{
	$u = new Upload();
	$u->onUpload(fn (...$postFiles) => assertType('array<int|string, array{error: int, name: string}>', $postFiles) || true);
}

// Immediately-invoked closure with variadic
function testImmediatelyInvoked(): void
{
	$result = (function (...$args): string {
		assertType('array<int|string, 1|3.14|\'hello\'>', $args);
		return implode(', ', $args);
	})(1, 'hello', 3.14);
}

// Immediately-invoked arrow function with variadic
function testImmediatelyInvokedArrow(): void
{
	$result = (fn (...$args) => assertType('array<int|string, 1|3.14|\'hello\'>', $args))(1, 'hello', 3.14);
}

// Variadic param with last callable parameter also variadic
/**
 * @param \Closure(string, int...): void $fx
 */
function variadicExpected(\Closure $fx): void
{
}

function testVariadicExpected(): void
{
	variadicExpected(function (...$args): void {
		assertType('array<int|string, int|string>', $args);
	});
}
