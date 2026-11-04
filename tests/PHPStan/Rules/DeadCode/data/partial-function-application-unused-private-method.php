<?php // lint >= 8.6

namespace PartialFunctionApplicationUnusedPrivateMethod;

final class Foo
{

	public function doFoo(): \Closure
	{
		return $this->usedByPartialApplication(1, ?);
	}

	public function doBar(): \Closure
	{
		return self::usedStatically(?);
	}

	private function usedByPartialApplication(int $a, int $b): int
	{
		return $a + $b;
	}

	private static function usedStatically(int $a): int
	{
		return $a;
	}

}
