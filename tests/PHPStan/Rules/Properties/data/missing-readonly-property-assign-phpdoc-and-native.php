<?php // lint >= 8.1

namespace MissingReadOnlyPropertyAssignPhpDocAndNative;

class Foo
{

	/** @readonly */
	private readonly int $assigned;

	private int $unassignedButNotReadOnly;

	private int $readBeforeAssignedNotReadOnly;

	/** @readonly */
	private readonly int $unassigned;

	/** @readonly */
	private readonly int $unassigned2;

	/** @readonly */
	private readonly int $readBeforeAssigned;

	/** @readonly */
	private readonly int $doubleAssigned;

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
