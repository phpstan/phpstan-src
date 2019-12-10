<?php

namespace YieldTypeRuleTest;

class Foo
{

	/**
	 * @return \Generator<string, int>
	 */
	public function doFoo(): \Generator
	{
		yield 'foo' => 1;
		yield 'foo' => 'bar';
		yield;
		yield 1;
		yield 'foo';
	}

	/**
	 * @return\Generator<array{0: \DateTime, 1: \DateTime, 2: \stdClass, 4: \DateTimeImmutable}>
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

}
