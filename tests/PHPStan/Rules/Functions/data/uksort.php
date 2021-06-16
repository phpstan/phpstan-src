<?php declare(strict_types = 1);

namespace UksortCallback;

class Foo
{

	public function doFoo()
	{
		$array = ['one' => 1, 'two' => 2, 'three' => 3];

		uksort(
			$array,
			function (\stdClass $one, \stdClass $two): int {
				return 1;
			}
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
		uksort($this->unknownKeys, function (string $one, string $two) {
			return 1;
		});
	}

	public function doFoo2(): void
	{
		uksort($this->stringKeys, function (string $one, string $two) {
			return 1;
		});
	}

	public function doFoo3(): void
	{
		uksort($this->intKeys, function (string $one, string $two) {
			return 1;
		});
	}

}
