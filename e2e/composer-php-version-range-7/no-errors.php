<?php declare(strict_types = 1);

namespace ComposerPhpVersionRange7;

use function PHPStan\Testing\assertType;

// composer.json requires "^7.4", so the analysed PHP version stays in the PHP 7.4 range
// no matter which PHP version PHPStan itself runs on.
assertType('int<70400, 70499>', PHP_VERSION_ID);

function variadicParameter(int ...$args): void
{
	// without named arguments a variadic parameter can only be a list
	assertType('list<int>', $args);
}

class Foo
{

	// no "cannot be final as it is never overridden by other classes" -
	// PHP 7 does not warn about final private methods
	final private function finalPrivateMethod(): void
	{
	}

	public function doFoo(): void
	{
		$this->finalPrivateMethod();
	}

}
