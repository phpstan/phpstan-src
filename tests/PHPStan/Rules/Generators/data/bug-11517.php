<?php declare(strict_types = 1);

namespace Bug11517;

class HelloWorld
{
	/**
	 * @return iterable<array-key, array{string}>
	 */
	public function bug(): iterable
	{
		yield from [];
	}

	/**
	 * @return iterable<array-key, object{a: string}>
	 */
	public function fine(): iterable
	{
		yield from [];
	}

	/**
	 * @return iterable<array-key, string>
	 */
	public function finetoo(): iterable
	{
		yield from [];
	}
}
