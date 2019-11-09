<?php

namespace YieldFromCheck;

use DateTimeImmutable;

class Foo
{

	/**
	 * @return \Generator<DateTimeImmutable, string>
	 */
	public function doFoo(): \Generator
	{
		yield from 1;
		yield from $this->doBar();
	}

	/**
	 * @return \Generator<\stdClass, int>
	 */
	public function doBar()
	{
		$stdClass = new \stdClass();
		yield $stdClass => 1;
	}

	public function doBaz()
	{
		$stdClass = new \stdClass();
		yield $stdClass => 1;
	}

	/**
	 * @return \Generator<mixed, mixed, int|null, mixed>
	 */
	public function generatorAcceptingIntOrNull(): \Generator
	{
		yield 1;
		yield 2;
		yield from $this->generatorAcceptingInt();
		yield from $this->generatorAcceptingIntOrNull();
	}

	/**
	 * @return \Generator<mixed, mixed, int, mixed>
	 */
	public function generatorAcceptingInt(): \Generator
	{
		yield 1;
		yield 2;
		yield from $this->generatorAcceptingInt();
		yield from $this->generatorAcceptingIntOrNull();
	}

}
