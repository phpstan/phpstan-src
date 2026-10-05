<?php declare(strict_types = 1);

namespace WalkTraceClosureBindThis;

// Closure::bind()'s scope factory: $this is the bound object refined by the
// scope class - each path of the refinement (no bound object, a bound object
// the scope narrows, one it cannot narrow, a union mixing both, a nullable
// one, a mixed one, a parent instance bound into a subclass scope, $this
// bound into another class's scope, an object or class-string scope,
// 'static', a static closure)

interface FooInterface
{

}

class Foo
{

	private int $secret = 1;

}

class SubFoo extends Foo
{

}

class NoParent
{

}

final class FinalNoParent
{

}

/**
 * @param class-string<Foo> $fooClass
 */
function bindAll(
	object $object,
	Foo $foo,
	SubFoo $subFoo,
	NoParent $noParent,
	FinalNoParent $finalNoParent,
	?Foo $nullableFoo,
	Foo|NoParent $fooOrNoParent,
	string $fooClass,
	$mixed,
): void
{
	\Closure::bind(function () {
		return $this;
	}, null, Foo::class);
	\Closure::bind(function () {
		return $this->secret;
	}, $object, Foo::class);
	\Closure::bind(fn () => $this, $subFoo, Foo::class);
	\Closure::bind(fn () => $this, $foo, Foo::class);
	\Closure::bind(fn () => $this, $noParent, FooInterface::class);
	\Closure::bind(fn () => $this, $noParent, Foo::class);
	\Closure::bind(fn () => $this, $finalNoParent, FooInterface::class);
	\Closure::bind(fn () => $this, $fooOrNoParent, Foo::class);
	\Closure::bind(fn () => $this, $nullableFoo, Foo::class);
	\Closure::bind(fn () => $this, $noParent);
	\Closure::bind(fn () => $this, $object, $foo);
	\Closure::bind(fn () => $this, $noParent, $foo);
	\Closure::bind(fn () => $this, $object, $fooClass);
	\Closure::bind(fn () => $this, $noParent, $fooClass);
	\Closure::bind(fn () => $this, $object, 'static');
	\Closure::bind(static fn () => $this, $object, Foo::class);
	\Closure::bind(fn () => $this, $mixed, Foo::class);
	\Closure::bind(fn () => $this, $foo, SubFoo::class);
}

class Outer
{

	public function bindThis(): void
	{
		\Closure::bind(fn () => $this, $this, Foo::class);
		\Closure::bind(fn () => $this, $this, FooInterface::class);
	}

}
