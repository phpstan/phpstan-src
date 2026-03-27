<?php

namespace E2EInTrait;

class Bar
{

	use FooTrait;

	public function getSth(): ?self
	{
		return rand(0, 1) ? $this : null;
	}

	public function getSth2(): self
	{
		return $this;
	}

}
