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
