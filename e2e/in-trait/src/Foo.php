<?php

namespace E2EInTrait;

class Foo
{

	use FooTrait;

	public function getSth(): self
	{
		return $this;
	}

	public function getSth2(): self
	{
		return $this;
	}

}
