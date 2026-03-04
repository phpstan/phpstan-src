<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13902;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public mixed $a = null;
	public mixed $b = null;

	/**
	 * @phpstan-assert int $this->a
	 * @return $this
	 */
	public function setA()
	{
		$this->a = 1;
		return $this;
	}
	/**
	 * @phpstan-assert int $this->b
	 * @return $this
	 */
	public function setB()
	{
		$this->b = 1;
		return $this;
	}
}

function test(): void
{
	$o = new HelloWorld;
	$o->setA()->setB();
	assertType('int', $o->a);
	assertType('int', $o->b);

	$o2 = new HelloWorld;
	$o2->setA();
	$o2->setB();
	assertType('int', $o2->a);
	assertType('int', $o2->b);
}
