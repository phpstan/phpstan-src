<?php

namespace Bug3523;

interface FooInterface
{
	/**
	 * @return static
	 */
	public function deserialize();
}

final class Foo implements FooInterface
{
	/**
	 * @return static
	 */
	public function deserialize(): self
	{
		return new self();
	}
}

class Bar implements FooInterface
{
	/**
	 * @return static
	 */
	public function deserialize(): self
	{
		return new self();
	}
}

class Baz implements FooInterface
{
	/**
	 * @return self
	 */
	public function deserialize(): self
	{
		return new self();
	}
}
