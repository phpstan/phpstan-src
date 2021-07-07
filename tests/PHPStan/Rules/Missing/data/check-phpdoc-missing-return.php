<?php // lint >= 8.0

namespace CheckPhpDocMissingReturn;

class Foo
{

	/**
	 * @return string|int
	 */
	public function doFoo()
	{

	}

	/**
	 * @return string|null
	 */
	public function doFoo2()
	{

	}

	/**
	 * @return string|int
	 */
	public function doFoo3()
	{
		if (rand()) {
			return 'foo';
		}
	}

	/**
	 * @return string|null
	 */
	public function doFoo4()
	{
		if (rand()) {
			return 'foo';
		}
	}

	/**
	 * @return mixed
	 */
	public function doFoo5()
	{
		if (rand()) {
			return 'foo';
		}
	}

}

class Bar
{

	public function doFoo(): string|int
	{

	}

	public function doFoo2(): ?string
	{

	}

	public function doFoo3(): string|int
	{
		if (rand()) {
			return 'foo';
		}
	}

	public function doFoo4(): ?string
	{
		if (rand()) {
			return 'foo';
		}
	}

	public function doFoo5(): mixed
	{
		if (rand()) {
			return 'foo';
		}
	}

}
