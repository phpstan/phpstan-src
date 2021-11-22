<?php

namespace ClassPhpDocsNamespace;

use function PHPStan\Testing\assertType;

/**
 * @property string $base
 *
 * @property string $foo
 * @phpstan-property int $foo
 *
 * @property-read string $bar
 * @phpstan-property-read int $bar
 *
 * @property-write string $baz
 * @phpstan-property-write int $baz
 */
class PhpstanProperties
{
	public function doFoo()
	{
		assertType('string', $this->base);
		assertType('int', $this->foo);
		assertType('int', $this->bar);
		assertType('int', $this->baz);
	}
}
