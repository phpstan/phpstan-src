<?php declare(strict_types = 1);

namespace UnusedMethodParametersValueFlow;

class Foo
{

	private function unusedParameter(int $input): void
	{
		while (rand(0, 1)) {
			$input = $input + 1;
		}
	}

	private function coveredParameter(int $input): void
	{
		$copy = $input + 1;
	}

	private function usedParameter(int $input): int
	{
		$copy = $input + 1;
		return $copy;
	}

	private function overwrittenParameter(int $input): int
	{
		$input = 1;
		return $input;
	}

	public function run(): void
	{
		$this->unusedParameter(1);
		$this->coveredParameter(1);
		$this->usedParameter(1);
		$this->overwrittenParameter(1);
	}

}
