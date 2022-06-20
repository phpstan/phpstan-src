<?php declare(strict_types = 1);

namespace Bug7492;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(array $config): void
	{
		$x = ($config['db'][1] ?? []) + ['host' => '', 'login' => '', 'password' => '', 'name' => ''];
		assertType('non-empty-array', $x);
	}
}
