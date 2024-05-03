<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug9863;

class ReadonlyParentWithoutIsset
{
	public function __construct(
		public readonly int $foo
	) {}
}

class ReadonlyChildWithoutIsset extends ReadonlyParentWithoutIsset
{
	public function __construct(
		public readonly int $foo = 42
	) {
		parent::__construct($foo);
	}
}

class ReadonlyParentWithIsset
{
	public readonly int $foo;

	public function __construct(
		int $foo
	) {
		if (! isset($this->foo)) {
			$this->foo = $foo;
		}
	}
}

class ReadonlyChildWithIsset extends ReadonlyParentWithIsset
{
	public function __construct(
		public readonly int $foo = 42
	) {
		parent::__construct($foo);
	}
}

$a = new ReadonlyParentWithoutIsset(0);
$b = new ReadonlyChildWithoutIsset();
$c = new ReadonlyChildWithoutIsset(1);

$x = new ReadonlyParentWithIsset(2);
$y = new ReadonlyChildWithIsset();
$z = new ReadonlyChildWithIsset(3);
