<?php declare(strict_types=1);

namespace Bug7056;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	protected string $foo = 'bar';

	public function a(int $x): void
	{
		$ref = new \ReflectionClass($this);
		assertType('class-string<$this(Bug7056\HelloWorld)>', $ref->getName());

		$property = $ref->getProperty('foo');
		assertType('non-empty-string', $property->getName());

		$method = $ref->getMethod('a');
		assertType('non-empty-string', $method->getName());

		$m = new \ReflectionMethod($this, 'a');
		assertType('non-empty-string', $m->getName());

		$params = $m->getParameters();
		assertType('non-empty-string', $params[0]->getName());

		$rf = new \ReflectionFunction('Bug7056\fooo');
		assertType('non-empty-string', $rf->getName());
	}
}

function fooo() {}
