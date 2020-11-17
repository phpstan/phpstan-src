<?php // lint >= 8.0

namespace NamedArgumentsIntegrationTest;

class Foo
{

	public function doFoo(
		int $i,
		int $j,
		int $k, ?int $l = null
	): void
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
			j: 3
		);
		$this->doFoo(
			'foo',
			j: 3,
			k: 4
		);
		$this->doFoo(
			i: 'foo',
			j: 3,
			k: 4
		);
		$this->doFoo(i: 1, ...['j' => 2, 'k' => 3]);
		$this->doFoo(...['k' => 3, 'i' => 1, 'j' => 'str']);
		$this->doFoo(...['k' => 3, 'i' => 1, 'str']);
	}

	public function doBaz(self $self): void
	{
		$self->doFoo(
			i: 1,
			2,
			3
		);
		$self->doFoo(
			1,
			j: 3
		);
		$self->doFoo(
			'foo',
			j: 3,
			k: 4
		);
		$self->doFoo(
			i: 'foo',
			j: 3,
			k: 4
		);
		$self->doFoo(i: 1, ...['j' => 2, 'k' => 3]);
		$self->doFoo(...['k' => 3, 'i' => 1, 'j' => 'str']);
		$self->doFoo(...['k' => 3, 'i' => 1, 'str']);
	}

}
