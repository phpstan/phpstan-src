<?php

namespace NamedArgumentsMethod;

class Foo
{

	public function doFoo(
		int $i,
		int $j,
		int $k, ?int $l = null
	)
	{

	}

	public function doBar(): void
	{
		$this->doFoo(
			i: 1,
			2,
			3
		);
		$this->doFoo(
			1,
			i: 1,
			j: 2,
			k: 3
		);
		$this->doFoo(
			i: 1,
			i: 2,
			j: 3,
			k: 4
		);

		$this->doFoo(
			1,
			j: 3
		);

		$this->doFoo(
			1,
			2,
			3,
			z: 4
		);

		$this->doFoo(
			'foo',
			j: 2,
			k: 3
		);

		$this->doFoo(
			1,
			j: 'foo',
			k: 3
		);

	}

	public function doBaz(&$i): void
	{

	}

	public function doLorem(?\stdClass $foo): void
	{
		$this->doBaz(i: 1);
		$this->doBaz(i: $foo?->bar);

		$this->doFoo(i: 1, ...['j' => 2, 'k' => 3]);

		$this->doFoo(...['k' => 3, 'i' => 1, 'j' => 'str']);

		$this->doFoo(...['k' => 3, 'i' => 1, 'str']);
	}

	public function doIpsum(int $a, int $b, string ...$args): void
	{

	}

	public function doDolor(): void
	{
		$this->doIpsum(...[1, 2, 3, 'foo' => 'foo']);
		$this->doIpsum(...[1, 2, 'foo' => 'foo']);
		$this->doIpsum(...['a' => 1, 'b' => 2, 'foo' => 'foo']);
		$this->doIpsum(...['a' => 1, 'b' => 'foo', 'foo' => 'foo']);
		$this->doIpsum(...['a' => 1, 'b' => 'foo', 'foo' => 1]);
		$this->doIpsum(...['a' => 1, 'foo' => 'foo']);
		$this->doIpsum(...['b' => 1, 'foo' => 'foo']);
		$this->doIpsum(...[1, 2], 'foo');
	}

}
