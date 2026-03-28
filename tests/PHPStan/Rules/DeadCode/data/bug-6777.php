<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug6777;

class HelloWorld
{
	/** @param \ArrayObject<int, string> $array */
	public function __construct(private \ArrayObject $array){}

	public function send(string $s) : void{
		$this->array[] = $s;
	}
}

class WithThreaded
{
	private \Threaded $collection;

	public function __construct()
	{
		$this->collection = new \Threaded();
	}

	public function add(string $s): void
	{
		$this->collection[] = $s;
	}
}

class WithUnionObjectArray
{
	/** @var \ArrayObject<int, string>|array<int, string> */
	private \ArrayObject|array $collection;

	/** @param \ArrayObject<int, string>|array<int, string> $collection */
	public function __construct(\ArrayObject|array $collection)
	{
		$this->collection = $collection;
	}

	public function add(string $s): void
	{
		$this->collection[] = $s;
	}
}

class WithUnionObjectString
{
	/** @var \ArrayObject<int, string>|string */
	private \ArrayObject|string $collection;

	/** @param \ArrayObject<int, string>|string $collection */
	public function __construct(\ArrayObject|string $collection)
	{
		$this->collection = $collection;
	}

	public function add(string $s): void
	{
		$this->collection[] = $s;
	}
}
