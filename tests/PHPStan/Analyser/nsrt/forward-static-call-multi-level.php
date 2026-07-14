<?php declare(strict_types = 1);

namespace ForwardStaticCallMultiLevel;

use function PHPStan\Testing\assertType;

/**
 * Multi-level hierarchy: Root -> Middle -> Leaf (final).
 * Each class has its own which() implementation with a distinct return type, so the
 * assertions below can tell exactly which implementation the call is resolved against.
 */
class Root
{

	/** @return 'Root' */
	public static function which(): string
	{
		return 'Root';
	}

	/** @return static */
	public static function create(): static
	{
		return new static(); // @phpstan-ignore new.static
	}

}

class Middle extends Root
{

	/** @return 'Middle' */
	public static function which(): string // @phpstan-ignore method.childReturnType
	{
		return 'Middle';
	}

	/** @return static */
	public static function make(): static
	{
		return new static(); // @phpstan-ignore new.static
	}

	public static function test(): void
	{
		// Naming an ancestor calls the *named* class's implementation (runtime returns
		// 'Root' even though Middle and Leaf override which()), while the late static
		// binding is forwarded.
		assertType("'Root'", forward_static_call([Root::class, 'which']));
		assertType("'Root'", forward_static_call('ForwardStaticCallMultiLevel\Root::which'));

		// The forwarded binding is the caller's static: at runtime Middle::test() gives
		// Middle and Leaf::test() gives Leaf, both covered by static(Middle).
		assertType('static(ForwardStaticCallMultiLevel\Middle)', forward_static_call([Root::class, 'create']));
		assertType('static(ForwardStaticCallMultiLevel\Middle)', forward_static_call('ForwardStaticCallMultiLevel\Root::create'));

		// self::class names this very class; its own implementation is called.
		assertType("'Middle'", forward_static_call([self::class, 'which']));
		assertType('static(ForwardStaticCallMultiLevel\Middle)', forward_static_call([self::class, 'make']));

		// Naming a descendant: at runtime the binding is forwarded only when the caller's
		// runtime static is a subclass of the named class (Leaf::test() gives Leaf,
		// Middle::test() gives Leaf too because the binding resets to the named class),
		// so the named class's object type covers both outcomes.
		assertType('ForwardStaticCallMultiLevel\Leaf', forward_static_call([Leaf::class, 'create']));
	}

	public function instanceContext(): void
	{
		// An instance method also has an active class scope to forward
		// (at runtime the binding is the object's class).
		assertType('static(ForwardStaticCallMultiLevel\Middle)', forward_static_call([Root::class, 'create']));
	}

}

final class Leaf extends Middle
{

	/** @return 'Leaf' */
	public static function which(): string // @phpstan-ignore method.childReturnType
	{
		return 'Leaf';
	}

	public static function test(): void
	{
		// Called from the final class, naming the two-levels-up ancestor: still the named
		// class's implementation (runtime returns 'Root', not this class's override), and
		// the forwarded binding collapses to the final class itself.
		assertType("'Root'", forward_static_call([Root::class, 'which']));
		assertType('ForwardStaticCallMultiLevel\Leaf', forward_static_call([Root::class, 'create']));

		// A method defined only in the intermediate class forwards the same way.
		assertType('ForwardStaticCallMultiLevel\Leaf', forward_static_call([Middle::class, 'make']));
	}

}
