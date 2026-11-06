<?php // lint >= 8.0

namespace Bug15197;

use function PHPStan\Testing\assertType;

interface DiInterface
{
	/**
	 * @param-closure-this DiInterface $definition
	 */
	public function set(string $name, mixed $definition): void;

	public function get(string $service): object;
}

class MyServiceProvider
{
	const SERVICE_FOO = 'foo';

	public function provide(DiInterface $di): void {
		$di->set('foobar', function () {
			assertType(DiInterface::class, $this);
			assertType("'foo'", self::SERVICE_FOO);
			assertType('object', $this->get(self::SERVICE_FOO));
		});
	}
}
