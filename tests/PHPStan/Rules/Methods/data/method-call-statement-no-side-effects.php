<?php

namespace MethodCallStatementNoSideEffects;

class Foo
{

	public function doFoo(\DateTime $dt)
	{
		$dt->modify('+1 month');
	}

	public function doBar(\DateTimeImmutable $dti)
	{
		$dti->modify('+1 month');
		$dti->createFromFormat('Y-m-d', '2019-07-24');
	}

	public function doBaz(\Exception $e)
	{
		$e->getCode();
	}

}

class Bar
{

	public function doFoo()
	{

	}

	/**
	 * @phpstan-pure
	 */
	public function doPure()
	{

	}

	/**
	 * @phpstan-pure
	 * @throws void
	 */
	public function doPureWithThrowsVoid()
	{

	}

	/**
	 * @phpstan-pure
	 * @throws \Exception
	 */
	public function doPureWithThrowsException()
	{

	}

	public function doBar(): void
	{
		$this->doFoo();
		$this->doPure(); // report
		$this->doPureWithThrowsVoid(); // report
		$this->doPureWithThrowsException(); // do not report
	}

}
