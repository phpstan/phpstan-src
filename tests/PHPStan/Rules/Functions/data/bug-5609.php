<?php

namespace Bug5609;

class Foo
{

	/** @var \stdClass[] */
	private $entities;

	public function doFoo()
	{
		array_filter($this->entities, function (\stdClass $std): bool {
			return true;
		});
	}

}
