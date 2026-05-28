<?php // lint >= 8.0

namespace Bug14715CallMethods;

final class Foo
{

	public function bar(int $a): void
	{
	}

	/**
	 * @param array{0: int} $sealed
	 * @param array{0: string, ...<int, bool>} $unsealed
	 */
	public function test(array $sealed, array $unsealed, bool $cond): void
	{
		$x = $cond ? $sealed : $unsealed;
		$this->bar(...$x);
	}

}
