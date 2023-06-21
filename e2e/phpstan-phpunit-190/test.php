<?php // lint >= 8.1

namespace PhpstanPhpUnit190;

class FoobarTest
{
	public function testBaz(): int
	{
		$matcher = new self();
		$this->acceptCallback(static function (string $test) use ($matcher): string {
			match ($matcher->testBaz()) {
				1 => 1,
				2 => 2,
				default => new \LogicException()
			};

			return $test;
		});

		return 1;
	}

	public function acceptCallback(callable $cb): void
	{

	}
}
