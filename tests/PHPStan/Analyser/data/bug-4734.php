<?php

namespace Bug4734;

use function PHPStan\Analyser\assertType;

class Foo
{
	private static bool $httpMethodParameterOverride = true;

	public static function getHttpMethodParameterOverride(): bool
	{
		return self::$httpMethodParameterOverride;
	}
}

class Bar
{
	private bool $override = false;

	public function test(): void
	{
		var_dump(Foo::getHttpMethodParameterOverride());

		if (!$this->override && Foo::getHttpMethodParameterOverride()) {
			$disableHttpMethodParameterOverride = \Closure::bind(static function (): void {
				self::$httpMethodParameterOverride = false;
			}, null, Foo::class);
			$disableHttpMethodParameterOverride();
		}

		var_dump(Foo::getHttpMethodParameterOverride());
	}
}
