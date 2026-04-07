<?php declare(strict_types = 1);

namespace Bug7088;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(\SimpleXMLElement $prop, string $x): void
	{
		assertType('(SimpleXMLElement|null)', $prop->foo);
		assertType('(SimpleXMLElement|null)', $prop->{'foo-bar'});
		assertType('(SimpleXMLElement|null)', $prop->{$x});
	}
}
