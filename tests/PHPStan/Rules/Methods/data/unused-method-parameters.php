<?php

namespace UnusedMethodParameters;

class Foo
{

	/** @var int */
	private $y = 0;

	public function __construct(int $constructorParameter)
	{
		$this->y = $constructorParameter;
	}

	public function publicMethod(int $unused): void
	{
	}

	protected function protectedMethod(int $unused): void
	{
	}

	private function completelyUnused(int $used, int $unused): int
	{
		return $used;
	}

	private function immediatelyReassigned(int $x): int
	{
		$x = 1;

		return $x;
	}

	private function readThenReassigned(int $x): int
	{
		$x = $x + 1;

		return $x;
	}

	private function reassignedInOneBranch(int $x): int
	{
		if (rand(0, 1) === 0) {
			$x = 1;
		}

		return $x;
	}

	private function byRefWritten(int &$x): void
	{
		$x = 1;
	}

	private function byRefUnused(int &$x): void
	{
		echo 'no use of the reference';
	}

	private function observedByFuncGetArgs(int $x): array
	{
		$x = 1;

		return [func_get_args(), $x];
	}

	private static function staticCompletelyUnused(int $unused): void
	{
		echo 'side effect only';
	}

	private function usedOnlyInClosureUse(int $x): callable
	{
		return function () use ($x): int {
			return $x;
		};
	}

	private function __invoke(int $magicParameter): void
	{
	}

	/**
	 * @param mixed $value
	 * @phpstan-assert-if-true int $value
	 */
	private function assertedParameterIsContract($value): bool
	{
		return true;
	}

	public function callThemAll(): void
	{
		$this->completelyUnused(1, 2);
		$this->immediatelyReassigned(1);
		$this->readThenReassigned(1);
		$this->reassignedInOneBranch(1);
		$a = 1;
		$this->byRefWritten($a);
		$this->byRefUnused($a);
		$this->observedByFuncGetArgs(1);
		self::staticCompletelyUnused(1);
		$this->usedOnlyInClosureUse(1);
		$this->assertedParameterIsContract(1);
	}

}
