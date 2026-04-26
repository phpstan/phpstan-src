<?php declare(strict_types = 1);

namespace Bug2861Assign;

class Foo {
	public static function test(): void {
		if (property_exists(static::class, 'default')) {
			static::$default = 'value';
		}
	}

	/**
	 * @param class-string $className
	 */
	public static function testExpr(string $className): void {
		if (property_exists($className, 'default')) {
			$className::$default = 'value';
		}
	}
}
