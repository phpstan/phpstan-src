<?php // lint >= 8.1

declare(strict_types = 1);

namespace IntersectionTemplateMixedMethod;

use function PHPStan\Testing\assertType;

class Foo
{

	public function bar(int $a): string
	{
		return '';
	}

	public function baz(bool $b = false): bool
	{
		return $b;
	}

}

/**
 * @template T
 * @param T&Foo $x
 */
function test($x): void
{
	assertType('Closure(int): string', $x->bar(...));
	assertType('Closure(bool=): bool', $x->baz(...));
	assertType('string', $x->bar(1));
}
