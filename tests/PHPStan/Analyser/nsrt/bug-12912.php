<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug12912;

use function PHPStan\Testing\assertType;

class Foo
{
	protected Bar $foo = Bar::Yes;

	public function foo(): void
	{
		if($this->foo === Bar::No) {
			return;
		}

		assertType('Bug12912\Bar::Yes', $this->foo);

		$this->wrap(fn() => assertType('Bug12912\Bar', $this->foo));

		$this->wrap(function() { assertType('Bug12912\Bar', $this->foo); });
	}

	public function wrap(callable $callback): void
	{
		$callback();
	}
}

enum Bar: string
{
    case Yes = 'yes';
    case No = 'no';
}
