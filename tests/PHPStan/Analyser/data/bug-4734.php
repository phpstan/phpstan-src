<?php

namespace Bug4734;

use function PHPStan\Analyser\assertType;

class Foo
{
	/**
	 * @var bool
	 */
	private static $httpMethodParameterOverride = true;

	/**
	 * @var bool
	 */
	private $httpMethodParameterOverride2 = true;
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

		$disableHttpMethodParameterOverride3 = \Closure::bind(function (): void {
			static::$httpMethodParameterOverride3 = false;
		}, new Foo(), Foo::class);
		$disableHttpMethodParameterOverride3();

		$disableHttpMethodParameterOverride4 = \Closure::bind(function (): void {
			$this->httpMethodParameterOverride4 = false;
		}, new Foo(), Foo::class);
		$disableHttpMethodParameterOverride4();
	}
}
