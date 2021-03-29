<?php

namespace Bug4734;

use function PHPStan\Analyser\assertType;

class Foo
{
	private static bool $httpMethodParameterOverride = true;

	private bool $httpMethodParameterOverride2 = true;
}

class Bar
{
	public function test(): void
	{
		$disableHttpMethodParameterOverride = \Closure::bind(static function (): void {
			static::$httpMethodParameterOverride = false;
		}, new Foo(), Foo::class);
		$disableHttpMethodParameterOverride();

		$disableHttpMethodParameterOverride2 = \Closure::bind(function (): void {
			$this->httpMethodParameterOverride2 = false;
		}, new Foo(), Foo::class);
		$disableHttpMethodParameterOverride2();
	}
}
