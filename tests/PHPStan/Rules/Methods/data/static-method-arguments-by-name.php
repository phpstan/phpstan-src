<?php // lint >= 8.0

namespace StaticMethodArgumentsByName;

class Foo
{

	/**
	 * @optional-arguments-by-name
	 */
	public static function optional(string $dialect, string $group = 'default'): void
	{

	}

	/**
	 * @arguments-by-name
	 */
	public static function all(string $dialect, string $group = 'default'): void
	{

	}

	public function doBar(): void
	{
		self::optional('openapi-3.1', group: 'read');
		self::optional('openapi-3.1', 'read');
		self::all(dialect: 'openapi-3.1');
		self::all('openapi-3.1');
		ClassLevel::doFoo('openapi-3.1', 'read');
	}

}

/**
 * @arguments-by-name
 */
class ClassLevel
{

	public static function doFoo(string $dialect, string $group = 'default'): void
	{

	}

}
