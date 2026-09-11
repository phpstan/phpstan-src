<?php declare(strict_types = 1);

namespace VariableOverwritingLoop;

class Foo
{

	/** @param string[] $a */
	public function spentLoopVariable(array $a, array $b): void
	{
		foreach ($a as $x) {
			echo $x;
		}
		foreach ($b as $x) {
			echo $x;
		}
	}

	/** @param string[] $a */
	public function readAfterLoop(array $a, string $x): void
	{
		foreach ($a as $x) {
		}
		echo $x;
	}

	/** @param string[] $a */
	public function freshVariableReadAfterLoop(array $a): void
	{
		foreach ($a as $x) {
		}
		echo $x;
	}

	/** @param string[] $a */
	public function notReadAfterLoop(array $a, string $x): void
	{
		foreach ($a as $x) {
			echo $x;
		}
	}

	/** @param string[] $a */
	public function definednessProvedByFlag(array $catches, array $exceptions): void
	{
		$hasGeneralCatch = false;
		foreach ($catches as $catch) {
			if ($catch === 'x') {
				$hasGeneralCatch = true;
				break;
			}
		}
		if (!$hasGeneralCatch) {
			return;
		}

		foreach ($exceptions as $exception) {
			foreach ($catches as $catch) {
				echo $catch;
			}
		}
	}

	/** @param string[] $outer */
	public function outerLoopVariableClobbered(array $outer, array $inner): void
	{
		foreach ($outer as $x) {
			foreach ($inner as $x) {
				echo $x;
			}
			echo $x;
		}
	}

	/** @param string[] $a */
	public function keyOverwrite(array $a, string $k, string $v): void
	{
		foreach ($a as $k => $v) {
		}
		echo $k;
	}

	/** @param string[] $a */
	public function conditionalAssignmentBeforeLoop(array $a, bool $c): void
	{
		if ($c) {
			$x = 'default';
		}
		foreach ($a as $x) {
		}
		echo $x;
	}

	/** @param string[] $a */
	public function reassignedInBody(array $a, string $x): void
	{
		foreach ($a as $x) {
			$x = trim($x);
		}
		echo $x;
	}

	/** @param string[] $a */
	public function reassignedAfterLoop(array $a, string $x): void
	{
		foreach ($a as $x) {
		}
		$x = 'other';
		echo $x;
	}

	/** @param array<array{string, string}> $a */
	public function listTarget(array $a, string $b, string $c): void
	{
		foreach ($a as [$b, $c]) {
		}
		echo $b;
	}

	/** @param string[] $a */
	public function referenceBeforeLoop(array $a): void
	{
		$x = 'x';
		$this->byRef($x);
		foreach ($a as $x) {
		}
		echo $x;
	}

	public function byRef(string &$s): void
	{
	}

	/** @param string[] $a */
	public function offsetWriteBeforeLoop(array $a, array $arr): void
	{
		$arr[] = 'x';
		foreach ($a as $arr) {
		}
		echo $arr;
	}

	public function forLoop(int $i, int $j): void
	{
		for ($i = 0; $i < 10; $i++) {
		}
		echo $i;

		for ($j = 0; $j < 10; $j++) {
		}
	}

	public function sequentialForLoops(): void
	{
		for ($i = 0; $i < 10; $i++) {
		}
		for ($i = 0; $i < 5; $i++) {
		}
		echo $i;
	}

	public function forLoopFlag(int $n): void
	{
		$found = false;
		for ($i = 0; $i < $n; $i++) {
			if ($i === 3) {
				$found = true;
				break;
			}
		}
		if (!$found) {
			return;
		}
		for ($i = 0; $i < $n; $i++) {
			echo $i;
		}
	}

	/** @param array{int, int} $b */
	public function forLoopList(int $i, int $j, array $b): void
	{
		for ([$i, $j] = $b; $i < 10; $i++) {
		}
		echo $i;
	}

	public function forLoopUpdateOnly(int $i): void
	{
		for (; $i < 10; $i++) {
		}
		echo $i;
	}

	/** @param string[] $a */
	public function unsetBeforeLoop(array $a, string $x): void
	{
		unset($x);
		foreach ($a as $x) {
		}
		echo $x;
	}

	/** @param string[] $a */
	public function readInLaterOuterIteration(array $outer, array $a): void
	{
		$x = 'initial';
		foreach ($outer as $o) {
			echo $x;
			foreach ($a as $x) {
			}
		}
	}

	/** @param string[] $a */
	public function closureBoundary(array $a, string $x): void
	{
		$f = function () use ($a, $x): void {
			foreach ($a as $x) {
			}
			echo $x;
		};
		$f();
		echo $x;
	}

}
