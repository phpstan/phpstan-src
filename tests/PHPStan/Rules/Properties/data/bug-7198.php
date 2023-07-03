<?php

namespace Bug7198;

trait TestTrait {
	public function foo(): void
	{
		$this->callee->foo();
	}
}

class TestCallee {
	public function foo(): void
	{
		echo "FOO\n";
	}
}

class TestCaller {
	use TestTrait;

	public function __construct(private readonly TestCallee $callee)
	{
		$this->foo();
	}
}

class TestCaller2 {
	public function foo(): void
	{
		$this->callee->foo();
	}

	public function __construct(private readonly TestCallee $callee)
	{
		$this->foo();
	}
}

class TestCaller3 {

	public function __construct(private readonly TestCallee $callee)
	{
		$this->foo();
	}

	public function foo(): void
	{
		$this->callee->foo();
	}
}
