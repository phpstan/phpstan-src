<?php declare(strict_types = 1);

namespace Discussion14038;

use DateTime;
use Exception;

class HelloWorld
{
	private ?DateTime $d = null;

	private function clo(callable $c, DateTime $d): void
	{
	}

	protected function redirect(): void
	{
	}

	public function sayHello(): void
	{
		if ($this->d === null) {
			throw new Exception;
		}
		$this->clo(
			function(): void {
				$this->redirect();
			},
			$this->d,
		);
	}
}

class ByRefTest
{
	private function foo(callable $c, string $s): void
	{
	}

	public function testByRef(): void
	{
		$x = 'hello';
		$this->foo(
			function() use (&$x): void {
				$x = 42;
			},
			$x,
		);
	}
}
