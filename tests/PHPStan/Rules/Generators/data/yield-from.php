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

	/**
	 * @return \Generator<array{0: \DateTime, 1: \DateTime, 2: \stdClass, 4: \DateTimeImmutable}>
	 */
	public function doArrayShape(): \Generator
	{
		yield [
			new \DateTime(),
			new \DateTime(),
			new \stdClass,
			new \DateTimeImmutable('2019-10-26'),
		];
	}

	/**
	 * @return \Generator<array{0: \DateTime, 1: \DateTime, 2: \stdClass, 3: \DateTimeImmutable}>
	 */
	public function doArrayShape2(): \Generator
	{
		yield from $this->doArrayShape();
	}

	/**
	 * @return \Generator<int, int>
	 */
	public function yieldWithImplicitReturn() : \Generator{
		yield 1;
		return 1;
	}

	/**
	 * @return \Generator<int, int, void, int>
	 */
	public function yieldWithExplicitReturn() : \Generator{
		yield 1;
		return 1;
	}

	/**
	 * @return \Generator<int, int, void, void>
	 */
	public function yieldWithVoidReturn() : \Generator{
		yield 1;
	}

	/**
	 * @return \Generator<int, int, void, void>
	 */
	public function yieldFromResult() : \Generator{
		yield from $this->yieldWithImplicitReturn();
		$mixed = yield from $this->yieldWithImplicitReturn();

		yield from $this->yieldWithExplicitReturn();
		$int = yield from $this->yieldWithExplicitReturn();

		yield from $this->yieldWithVoidReturn();
		$void = yield from $this->yieldWithVoidReturn();
	}

	/**
	 * @return \Generator<array{opt?: int, req: int}>
	 */
	public function yieldNamedArrayShape(): \Generator
	{
		yield from [['req' => 1, 'foo' => 1]];
		yield from [
			rand()
				? ['req' => 1, 'opt' => 1]
				: ['req' => 1, 'foo' => 1],
		];
	}
}
