<?php

namespace MissingReadOnlyPropertyAssignPhpDoc;

class Foo
{

	/**
	 * @var int
	 * @readonly
	 */
	private $assigned;

	/** @var int */
	private $unassignedButNotReadOnly;

	/** @var int */
	private $readBeforeAssignedNotReadOnly;

	/**
	 * @var int
	 * @readonly
	 */
	private $unassigned;

	/**
	 * @var int
	 * @readonly
	 */
	private $unassigned2;

	/**
	 * @var int
	 * @readonly
	 */
	private $readBeforeAssigned;

	/**
	 * @var int
	 * @readonly
	 */
	private $doubleAssigned;

	/** @var int */
	private $doubleAssignedNotReadOnly;

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

	/**
	 * @var int
	 * @readonly
	 */
	private $foo;

	public function setFoo(int $i)
	{
		// reported in ReadOnlyPropertyAssignRule
		$this->foo = $i;
		$this->foo = $i;
	}

}

class TestCase
{

	/**
	 * @var int
	 * @readonly
	 */
	private $foo;

	protected function setUp(): void
	{
		$this->foo = 1;
	}

}

class AssignOp
{

	/**
	 * @var int
	 * @readonly
	 */
	private $foo;

	/**
	 * @var int|null
	 * @readonly
	 */
	private $bar;

	public function __construct(int $foo)
	{
		$this->foo .= $foo;

		$this->bar = $this->bar ?? 3;
	}


}

class AssignRef
{

	/**
	 * @var int
	 * @readonly
	 */
	private $foo;

	public function __construct(int $foo)
	{
		$this->foo = &$foo;
	}

}
