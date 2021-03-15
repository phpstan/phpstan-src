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
