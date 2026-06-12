<?php // lint >= 8.3

namespace Bug12549;

class Foo
{
	public const string OPTION_ROUNDING_MODE = 'roundingMode';

	public function setRoundingMode(): void
	{
		$this->bar(self::OPTION_ROUNDING_MODE);
	}

	private function bar(string $v): void
	{
	}
}
