<?php

namespace Bug4680;

class HelloWorld
{
	/** @var \SplObjectStorage<self, null>|null */
	private $collection1;

	/** @var \SplObjectStorage<self, null> */
	private $collection2;

	public function __construct(): void
	{
		$this->collection1 = new \SplObjectStorage();
		$this->collection2 = new \SplObjectStorage();
	}
}
