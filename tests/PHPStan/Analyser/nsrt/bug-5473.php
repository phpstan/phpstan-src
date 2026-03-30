<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug5473;

use function PHPStan\Testing\assertType;

class AssertResult
{
	public function throws(\Throwable $exception): void
	{
		throw $exception;
	}
}

final class Assert
{
	/**
	 * @phpstan-assert !null $value
	 */
	public static function notNull(mixed $value): AssertResult
	{
		return new AssertResult();
	}
}

// Case 1: Standalone statement - WORKS
function testStandalone(?string $value): void
{
	Assert::notNull($value);
	assertType('string', $value);
}

// Case 2: Chained method call - BROKEN
function testChained(?string $value): void
{
	Assert::notNull($value)->throws(new \RuntimeException('Value is null'));
	assertType('string', $value);
}

// Case 3: Assigned to variable - WORKS
function testAssigned(?string $value): void
{
	$_ = Assert::notNull($value);
	assertType('string', $value);
}

// Case 4: assert() with && as expression statement
function testAssertAnd(?string $string): void
{
	assert($string !== null) && assert(strlen($string) > 1);
	assertType('non-falsy-string', $string);
}

// Case 5: Property access on nullable after chained assert
function testPropertyAccess(?object $cart): void
{
	Assert::notNull($cart)->throws(new \RuntimeException('Cart not found'));
	assertType('object', $cart);
}
