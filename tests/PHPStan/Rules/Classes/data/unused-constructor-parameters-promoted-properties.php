<?php

namespace UnusedConstructorParametersPromotedProperties;

class Foo
{

	private int $y;

	public function __construct(
		public int $x,
		int $y
	)
	{
		$this->y = $y;
	}

}
