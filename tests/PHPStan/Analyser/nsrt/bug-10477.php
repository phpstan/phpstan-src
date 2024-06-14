<?php

namespace Bug10477;

use function PHPStan\Testing\assertType;

class A
{
	protected $data;
	protected $foo = '';

	public function a(): void
	{
		assertType('mixed', $this->foo);
		assertType('$this(Bug10477\A)', $this);
		(new B())->foo($this);
		assertType('mixed', $this->foo);
		assertType('$this(Bug10477\A)', $this);
		if (isset($this->data['test'])) {
			$this->foo = $this->data['test'];
		}
	}
}

class B
{
	public function foo(mixed &$var): void {}
}
