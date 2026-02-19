<?php // lint >= 8.1

namespace Bug13596;

use Closure;

class BaseClass
{
	public function getCallable(): ?Closure
	{
		return method_exists($this, 'myCallable') ? $this->myCallable(...) : null;
	}

	public function getCallableWithIsCallable(): ?Closure
	{
		return is_callable([$this, 'myCallable']) ? $this->myCallable(...) : null;
	}
}

class ChildOne extends BaseClass
{
	//
}

class ChildTwo extends BaseClass
{
	public function myCallable(): string
	{
		return 'I exist on child two.';
	}
}
