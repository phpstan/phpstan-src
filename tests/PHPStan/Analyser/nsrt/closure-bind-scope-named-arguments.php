<?php // lint >= 8.0

declare(strict_types = 1);

namespace ClosureBindScopeNamedArguments;

use Closure;
use function PHPStan\Testing\assertType;

class Foo
{

	protected const A = 'Foo';

}

final class Bar extends Foo
{

	protected const A = 'Bar';

}

// The bind scope is resolved by argument name, so any order works.
assertType("'Foo'", Closure::bind(closure: static fn () => self::A, newThis: null, newScope: Foo::class)());
assertType("'Foo'", Closure::bind(closure: static fn () => self::A, newScope: Foo::class, newThis: null)());
assertType("'Bar'", Closure::bind(newScope: Bar::class, closure: static fn () => self::A, newThis: null)());

// Positional and named arguments mixed together.
assertType("'Foo'", Closure::bind(static fn () => parent::A, null, newScope: Bar::class)());
