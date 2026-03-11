<?php // lint < 8.1

namespace PropertiesNamespacePhp81;

use PropertiesNamespace\Bar;
use function PHPStan\Testing\assertType;

use SomeNamespace\Amet as Dolor;
use SomeGroupNamespace\{One, Two as Too, Three};

/**
 * @property-read string $documentElement
 */
abstract class Foo extends Bar
{
	public function doFoo()
	{
		assertType('string', $this->documentElement);
	}

}
