<?php // lint >= 8.1

namespace MissingReadOnlyPropertyAssign;

class Foo
{

	private readonly int $assigned;

	private int $unassignedButNotReadOnly;

	private int $readBeforeAssignedNotReadOnly;

	private readonly int $unassigned;

	private readonly int $unassigned2;

	private readonly int $readBeforeAssigned;

	private readonly int $doubleAssigned;

	private int $doubleAssignedNotReadOnly;

	public function __construct(
		private readonly int $promoted,
	)
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

	private readonly int $foo;

	public function setFoo(int $i)
	{
		// reported in ReadOnlyPropertyAssignRule
		$this->foo = $i;
		$this->foo = $i;
	}

}

class TestCase
{

	private readonly int $foo;

	protected function setUp(): void
	{
		$this->foo = 1;
	}

}

class AssignOp
{

	private readonly int $foo;

	private readonly ?int $bar;

	public function __construct(int $foo)
	{
		$this->foo .= $foo;

		$this->bar ??= 3;
	}


}

class AssignRef
{

	private readonly int $foo;

	public function __construct(int $foo)
	{
		$this->foo = &$foo;
	}

}

trait FooTrait
{

	private readonly int $assigned;

	private int $unassignedButNotReadOnly;

	private int $readBeforeAssignedNotReadOnly;

	private readonly int $unassigned;

	private readonly int $unassigned2;

	private readonly int $readBeforeAssigned;

	private readonly int $doubleAssigned;

	private int $doubleAssignedNotReadOnly;

	public function setUnassigned2(int $i): void
	{
		$this->unassigned2 = $i;
	}

}

class FooTraitClass
{

	use FooTrait;

	public function __construct(
		private readonly int $promoted,
	)
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

	private readonly int $id; // does not complain about being uninitialized because of a ReadWritePropertiesExtension

}

trait BarTrait
{

	public readonly int $foo;

	public function __construct(public readonly int $bar)
	{
		$this->foo = 17;
	}

}

class BarClass
{

	use BarTrait;

}

class AdditionalAssignOfReadonlyPromotedProperty
{

	public function __construct(private readonly int $x)
	{
		$this->x = 2;
	}

}

class MethodCalledFromConstructorAfterAssign
{


	private readonly int $foo;

	public function __construct()
	{
		$this->foo = 1;
		$this->doFoo();
	}

	public function doFoo(): void
	{
		echo $this->foo;
	}

}

class MethodCalledFromConstructorBeforeAssign
{


	private readonly int $foo;

	public function __construct()
	{
		$this->doFoo();
		$this->foo = 1;
	}

	public function doFoo(): void
	{
		echo $this->foo;
	}

}

class MethodCalledTwice
{
	private readonly int $foo;

	public function __construct()
	{
		$this->doFoo();
		$this->foo = 1;
		$this->doFoo();
	}

	public function doFoo(): void
	{
		echo $this->foo;
	}
}
