<?php

namespace Bug14645;

use function PHPStan\Testing\assertType;

final class C
{
	public function __construct(private ?self $a, private ?self $b)
	{
	}

	public function check(): bool
	{
		if ($this->a !== null && $this->b !== null) {
			throw new \LogicException();
		}

		if ($this->a !== null) {
			$this->a->check();
		}

		if ($this->b !== null) {
			assertType('Bug14645\C', $this->b);
			$this->b->check();
		}

		return true;
	}
}

final class VariableAnalog
{
	public function test(?\stdClass $a, ?\stdClass $b): void
	{
		if ($a !== null && $b !== null) {
			throw new \LogicException();
		}

		if ($a !== null) {
			echo $a->foo;
		}

		if ($b !== null) {
			assertType('stdClass', $b);
			echo $b->bar;
		}
	}
}

final class ReversedOrder
{
	public function __construct(private ?self $a, private ?self $b)
	{
	}

	public function check(): bool
	{
		if ($this->a !== null && $this->b !== null) {
			throw new \LogicException();
		}

		if ($this->b !== null) {
			$this->b->check();
		}

		if ($this->a !== null) {
			assertType('Bug14645\ReversedOrder', $this->a);
			$this->a->check();
		}

		return true;
	}
}

final class ThreeProperties
{
	public function __construct(
		private ?self $a,
		private ?self $b,
		private ?self $c,
	) {
	}

	public function check(): bool
	{
		if ($this->a !== null && $this->b !== null) {
			throw new \LogicException();
		}

		if ($this->a !== null && $this->c !== null) {
			throw new \LogicException();
		}

		if ($this->a !== null) {
			$this->a->check();
		}

		if ($this->b !== null) {
			assertType('Bug14645\ThreeProperties', $this->b);
			$this->b->check();
		}

		if ($this->c !== null) {
			assertType('Bug14645\ThreeProperties', $this->c);
			$this->c->check();
		}

		return true;
	}
}
