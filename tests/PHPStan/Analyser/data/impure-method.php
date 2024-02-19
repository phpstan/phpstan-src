<?php

namespace ImpureMethod;

use function PHPStan\Testing\assertType;

/**
 * @method $this phpDocReturnThis()
 */
class Foo
{

	/** @var int */
	private $fooProp;

	public function voidMethod(): void
	{
		$this->fooProp = rand(0, 1);
	}

	/**
	 * @return $this
	 */
	public function returnsThis()
	{
		$this->fooProp = rand(0, 1);
	}

	public function ordinaryMethod(): int
	{
		return 1;
	}

	/**
	 * @phpstan-impure
	 * @return int
	 */
	public function impureMethod(): int
	{
		$this->fooProp = rand(0, 1);

		return $this->fooProp;
	}

	/**
	 * @impure
	 * @return int
	 */
	public function impureMethod2(): int
	{
		$this->fooProp = rand(0, 1);

		return $this->fooProp;
	}

	public function doFoo(): void
	{
		$this->fooProp = 1;
		assertType('1', $this->fooProp);

		$this->voidMethod();
		assertType('int', $this->fooProp);
	}

	public function doFluent(): void
	{
		$this->fooProp = 1;
		assertType('1', $this->fooProp);

		$this->returnsThis();
		assertType('int', $this->fooProp);
	}

	public function doFluent2(): void
	{
		$this->fooProp = 1;
		assertType('1', $this->fooProp);

		$this->phpDocReturnThis();
		assertType('int', $this->fooProp);
	}

	public function doBar(): void
	{
		$this->fooProp = 1;
		assertType('1', $this->fooProp);

		$this->ordinaryMethod();
		assertType('1', $this->fooProp);
	}

	public function doBaz(): void
	{
		$this->fooProp = 1;
		assertType('1', $this->fooProp);

		$this->impureMethod();
		assertType('int', $this->fooProp);
	}

	public function doLorem(): void
	{
		$this->fooProp = 1;
		assertType('1', $this->fooProp);

		$this->impureMethod2();
		assertType('int', $this->fooProp);
	}

}
