<?php

namespace TaggedUnionReturnCheck;

class HelloWorld
{
	/**
	 * @return array{updated: true, id: int}|array{updated: false, id: null}
	 */
	public function sayHello(): array
	{
		return ['updated' => false, 'id' => 5];
	}
}
