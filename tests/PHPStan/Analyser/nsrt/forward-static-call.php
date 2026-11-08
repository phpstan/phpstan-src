<?php declare(strict_types = 1);

namespace ForwardStaticCall;

use function PHPStan\Testing\assertType;

class Base
{

	/** @return static */
	public static function create(): static
	{
		return new static(); // @phpstan-ignore new.static
	}

	public static function name(): string
	{
		return static::class;
	}

}

class Caller extends Base
{

	public static function test(): void
	{
		// forward_static_call() forwards the caller's late static binding when the
		// named class is an ancestor (or self), unlike call_user_func().
		assertType('static(ForwardStaticCall\Caller)', forward_static_call([Base::class, 'create']));
		assertType('static(ForwardStaticCall\Caller)', forward_static_call('ForwardStaticCall\Base::create'));
		assertType('static(ForwardStaticCall\Caller)', forward_static_call_array([Base::class, 'create'], []));
		assertType('string', forward_static_call([Base::class, 'name']));

		assertType('static(ForwardStaticCall\Caller)', forward_static_call([self::class, 'create']));

		// call_user_func() is a non-forwarding call: static:: is reset to the named class.
		assertType('ForwardStaticCall\Base', call_user_func([Base::class, 'create']));

		// A class outside the caller's ancestry does not receive the forwarded binding.
		assertType('ForwardStaticCall\Other', forward_static_call([Other::class, 'create']));

		// Closures have their own lexical scope; just resolve the callable's return type.
		assertType('1', forward_static_call(static fn (): int => 1));
	}

}

class Other
{

	/** @return static */
	public static function create(): static
	{
		return new static(); // @phpstan-ignore new.static
	}

}

function outsideClass(): void {
	// Runtime would throw (no class scope is active), but the type is still resolvable.
	assertType('ForwardStaticCall\Base', forward_static_call([Base::class, 'create']));
}
