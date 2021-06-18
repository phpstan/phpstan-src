<?php declare(strict_types = 1); // lint >= 7.4

namespace UksortCallbackArrow;

class Foo
{

	public function doFoo()
	{
		$array = ['one' => 1, 'two' => 2, 'three' => 3];

		uksort(
			$array,
			fn (\stdClass $one, \stdClass $two): int => 1
		);
	}

}

class Bar
{

	/** @var Foo[] */
	private $unknownKeys;

	/** @var array<string, Foo> */
	private $stringKeys;

	/** @var array<int, Foo> */
	private $intKeys;

	public function doFoo(): void
	{
		uksort($this->unknownKeys, fn (string $one, string $two) => 1);
	}

	public function doFoo2(): void
	{
		uksort($this->stringKeys, fn (string $one, string $two) => 1);
	}

	public function doFoo3(): void
	{
		uksort($this->intKeys, fn (string $one, string $two) => 1);
	}

}
