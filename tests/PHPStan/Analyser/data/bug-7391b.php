<?php declare(strict_types = 1);

namespace Bug7391B;

use function PHPStan\Testing\assertType;

class Foo
{
	public function __construct(int $needsAtLeastOneArg) {
		assertType('int', $needsAtLeastOneArg);
	}

	public static function m() {
		assertType('Bug7391B\Foo', self::m());
		assertType('static(Bug7391B\Foo)', static::m());
		assertType('Bug7391B\Foo', (self::class)::m());
		assertType('static(Bug7391B\Foo)', (static::class)::m());
		assertType('Bug7391B\Foo', get_class(new self(2))::m());
		assertType('Bug7391B\Foo', get_class(new static(2))::m());

		throw new \Error('For static analysis only, return type is resolved purely by DynamicStaticMethodReturnTypeExtension');
	}
}

function () {
	assertType('Bug7391B\Foo', Foo::m());
	assertType('Bug7391B\Foo', (Foo::class)::m());
	$fooCl = Foo::class;
	assertType('Bug7391B\Foo', get_class(new $fooCl(2))::m());
};
