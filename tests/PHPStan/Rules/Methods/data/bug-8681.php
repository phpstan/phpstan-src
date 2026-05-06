<?php declare(strict_types = 1);

namespace Bug8681;

class HelloWorld
{
	/**
	 * @return array<string, string>
	 */
	public function test(): array
	{
		/** @var array $a */
		$a = [];
		return $a;
	}

	/**
	 * @return array<string, string>
	 */
	public function testExplicitMixed(): array
	{
		/** @var array<mixed, mixed> $a */
		$a = [];
		return $a;
	}

	/**
	 * @return iterable<string, string>
	 */
	public function testIterable(): iterable
	{
		/** @var iterable $a */
		$a = [];
		return $a;
	}

	/**
	 * @return array<string, array<string, int>>
	 */
	public function testNested(): array
	{
		/** @var array<string, array> $a */
		$a = [];
		return $a;
	}
}
