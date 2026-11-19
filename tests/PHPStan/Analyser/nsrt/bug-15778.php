<?php // lint >= 8.1

declare(strict_types=1);

namespace Bug15778;

use function PHPStan\Testing\assertType;

/** @return ($key is null ? null : string) */
function label(?string $key = null, ?string $locale = null): ?string
{
	return $key;
}

/** @return ($b is null ? null : string) */
function second(?string $a = null, ?string $b = null, ?string $key = null): ?string
{
	return $b;
}

class Foo
{

	/** @return ($key is null ? null : string) */
	public function label(?string $key = null, ?string $locale = null): ?string
	{
		return $key;
	}

	/** @return ($key is null ? null : string) */
	public static function staticLabel(?string $key = null, ?string $locale = null): ?string
	{
		return $key;
	}

	/** @return ($b is null ? null : string) */
	public function second(?string $a = null, ?string $b = null, ?string $key = null): ?string
	{
		return $b;
	}

}

function doFoo(Foo $foo): void
{
	assertType('string', label(locale: 'en', key: 'welcome'));
	assertType('string', label(...['locale' => 'en'], key: 'welcome'));
	assertType('string', label(...[], key: 'welcome'));
	assertType('string', second(...['x', 'y'], key: 'welcome'));
	assertType('null', second(...['x'], key: 'welcome'));
	assertType('array', array_slice(...[[1, 2, 3]], offset: 1));

	assertType('string', $foo->label(...['locale' => 'en'], key: 'welcome'));
	assertType('string', $foo->label(...[], key: 'welcome'));
	assertType('string', $foo->second(...['x', 'y'], key: 'welcome'));
	assertType('string', Foo::staticLabel(...['locale' => 'en'], key: 'welcome'));
	assertType('string', Foo::staticLabel(...[], key: 'welcome'));
}
