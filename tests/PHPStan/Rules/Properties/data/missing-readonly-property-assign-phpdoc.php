<?php // lint >= 7.4

namespace MissingReadOnlyPropertyAssignPhpDoc;

class Foo
{

	/** @readonly */
	private int $assigned;

	private int $unassignedButNotReadOnly;

	private int $readBeforeAssignedNotReadOnly;

	/** @readonly */
	private int $unassigned;

	/** @readonly */
	private int $unassigned2;

	/** @readonly */
	private int $readBeforeAssigned;

	/** @readonly */
	private int $doubleAssigned;

	private int $doubleAssignedNotReadOnly;

	public function __construct()
	{
		$this->assigned = 1;

		echo $this->readBeforeAssignedNotReadOnly;
		$this->readBeforeAssignedNotReadOnly = 1;

		echo $this->readBeforeAssigned;
		$this->readBeforeAssigned = 1;

		$this->doubleAssigned = 1;
		$this->doubleAssigned = 2;

		$this->doubleAssignedNotReadOnly = 1;
		$this->doubleAssignedNotReadOnly = 2;
	}

	public function setUnassigned2(int $i): void
	{
		$this->unassigned2 = $i;
	}

}

class BarDoubleAssignInSetter
{

	/** @readonly */
	private int $foo;

	public function setFoo(int $i)
	{
		// reported in ReadOnlyPropertyAssignRule
		$this->foo = $i;
		$this->foo = $i;
	}

}

class TestCase
{

	/** @readonly */
	private int $foo;

	protected function setUp(): void
	{
		$this->foo = 1;
	}

}

class AssignOp
{

	/** @readonly */
	private int $foo;

	/** @readonly */
	private ?int $bar;

	public function __construct(int $foo)
	{
		$this->foo .= $foo;

		$this->bar = $this->bar ?? 3;
	}


}

class AssignRef
{

	/** @readonly */
	private int $foo;

	public function __construct(int $foo)
	{
		$this->foo = &$foo;
	}

}

/** @phpstan-immutable */
class Immutable
{

	private int $assigned;

	private int $unassigned;

	private int $unassigned2;

	private int $readBeforeAssigned;

	private int $doubleAssigned;

	public function __construct()
	{
		$this->assigned = 1;

		echo $this->readBeforeAssigned;
		$this->readBeforeAssigned = 1;

		$this->doubleAssigned = 1;
		$this->doubleAssigned = 2;
	}

	public function setUnassigned2(int $i): void
	{
		$this->unassigned2 = $i;
	}

}

trait FooTrait
{

	/** @readonly */
	private int $assigned;

	private int $unassignedButNotReadOnly;

	private int $readBeforeAssignedNotReadOnly;

	/** @readonly */
	private int $unassigned;

	/** @readonly */
	private int $unassigned2;

	/** @readonly */
	private int $readBeforeAssigned;

	/** @readonly */
	private int $doubleAssigned;

	private int $doubleAssignedNotReadOnly;

	public function setUnassigned2(int $i): void
	{
		$this->unassigned2 = $i;
	}

}

class FooTraitClass
{

	use FooTrait;

	public function __construct()
	{
		$this->assigned = 1;

		echo $this->readBeforeAssignedNotReadOnly;
		$this->readBeforeAssignedNotReadOnly = 1;

		echo $this->readBeforeAssigned;
		$this->readBeforeAssigned = 1;

		$this->doubleAssigned = 1;
		$this->doubleAssigned = 2;

		$this->doubleAssignedNotReadOnly = 1;
		$this->doubleAssignedNotReadOnly = 2;
	}

}


class Entity
{

	/** @readonly */
	private int $id; // does not complain about being uninitialized because of a ReadWritePropertiesExtension

}

trait BarTrait
{

	/** @readonly */
	public int $foo;

	public function __construct()
	{
		$this->foo = 17;
	}

}

class BarClass
{

	use BarTrait;

}

/** @immutable */
class A
{

	public string $a;

}

class B extends A
{

	public string $b;

	public function __construct()
	{
		$b = $this->b;
	}

}

class C extends B
{

	public string $c;

	public function __construct()
	{
		$this->c = '';
		$this->c = '';
	}

}
