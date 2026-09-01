<?php declare(strict_types = 1);

// Twin of ../control/functions.php compiled under the trusted prefix.

namespace TrustedTypesFixture\Trusted;

use Closure;

function recv(int $x): int
{
	return $x;
}

function recvInit(int $x = 5): int
{
	return $x;
}

function ret(int $x): string
{
	return $x;
}

function variadic(int ...$xs): int
{
	return count($xs);
}

function toFloat(float $x): float
{
	return $x;
}

function retFloat(int $x): float
{
	return $x;
}

final class Holder
{

	public int $prop = 0;

	public function __construct(public int $promoted = 0)
	{
	}

	public function method(int $x): int
	{
		return $x;
	}

	public static function closure(): Closure
	{
		return static fn (int $x): int => $x;
	}

}
